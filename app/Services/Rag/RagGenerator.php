<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\AiGeneration;
use App\Models\ClauseVersion;
use App\Models\TemplateVersion;
use App\Services\Ai\AnthropicClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Orchestrates the end-to-end RAG flow: retrieve → assemble → call LLM →
 * persist as `AiGeneration` row with status=draft. The lawyer reviews
 * and approves later via the UI.
 *
 * The persisted row is the audit trail required by CLAUDE.md §6.5.
 *
 * Refinement: `refine()` continues the same Claude conversation with the
 * previous draft as assistant context + a new user instruction. Each
 * refinement creates a new AiGeneration linked by parent_id so the chain
 * is preserved.
 */
final class RagGenerator
{
    public function __construct(
        private readonly RagRetrievalService $retriever,
        private readonly PromptAssembler $assembler,
        private readonly AnthropicClient $anthropic,
    ) {}

    /**
     * @param  array<string, mixed>  $filledData
     * @param  Collection<int, ClauseVersion>  $verbatimClauses
     */
    public function draft(
        Model $subject,
        string $userIntent,
        array $filledData,
        Collection $verbatimClauses,
        ?TemplateVersion $template = null,
    ): AiGeneration {
        $retrievedChunks = $this->retriever->retrieve($userIntent);
        $prompt = $this->assembler->assemble(
            userIntent: $userIntent,
            retrievedChunks: $retrievedChunks,
            verbatimClauses: $verbatimClauses,
            filledData: $filledData,
            template: $template,
        );

        // Audit row is created BEFORE the LLM call so a failed call still
        // leaves a trace of what was sent.
        $generation = AiGeneration::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'parent_id' => null,
            'revision_kind' => 'initial',
            'model' => config('lexa.anthropic.model'),
            'prompt' => $this->serializePrompt($prompt),
            'retrieved_chunk_ids' => $retrievedChunks->pluck('id')->all(),
            'output' => '',
            'status' => 'draft',
        ]);

        try {
            $output = $this->anthropic->sendMessages($prompt['system'], $prompt['messages']);
        } catch (Throwable $e) {
            $generation->update([
                'output' => '[AI call failed: '.$e->getMessage().']',
                'status' => 'rejected',
            ]);

            throw $e;
        }

        $generation->update(['output' => $output]);

        return $generation;
    }

    /**
     * Conversational refinement: the previous draft becomes assistant context
     * and `userInstruction` is the lawyer's request for changes. Returns a
     * new AiGeneration row with parent_id pointing at the previous.
     */
    public function refine(AiGeneration $previous, string $userInstruction): AiGeneration
    {
        $chainMessages = $this->buildConversation($previous, $userInstruction);
        $systemPrompt = $this->extractSystemPrompt($previous);

        $generation = AiGeneration::create([
            'subject_type' => $previous->subject_type,
            'subject_id' => $previous->subject_id,
            'parent_id' => $previous->id,
            'revision_kind' => 'ai_refine',
            'model' => $previous->model,
            'prompt' => $systemPrompt."\n\n---\n\n".collect($chainMessages)
                ->map(fn ($m) => $m['role'].': '.$m['content'])
                ->implode("\n\n"),
            'user_instruction' => $userInstruction,
            'retrieved_chunk_ids' => $previous->retrieved_chunk_ids,
            'output' => '',
            'status' => 'draft',
        ]);

        try {
            $output = $this->anthropic->sendMessages($systemPrompt, $chainMessages);
        } catch (Throwable $e) {
            $generation->update([
                'output' => '[AI call failed: '.$e->getMessage().']',
                'status' => 'rejected',
            ]);

            throw $e;
        }

        $generation->update(['output' => $output]);

        return $generation;
    }

    /**
     * Record a manual edit by the lawyer as a new revision. No Claude call.
     */
    public function recordManualEdit(AiGeneration $previous, string $newOutput, ?string $note = null): AiGeneration
    {
        return AiGeneration::create([
            'subject_type' => $previous->subject_type,
            'subject_id' => $previous->subject_id,
            'parent_id' => $previous->id,
            'revision_kind' => 'manual_edit',
            'model' => 'manual',
            'prompt' => '[manual edit by lawyer]',
            'user_instruction' => $note,
            'retrieved_chunk_ids' => $previous->retrieved_chunk_ids,
            'output' => $newOutput,
            'status' => 'draft',
        ]);
    }

    /**
     * Rebuild the full Claude conversation from the chain so refinements
     * carry forward all prior context.
     *
     * @return array<int, array{role:string,content:string}>
     */
    private function buildConversation(AiGeneration $previous, string $newInstruction): array
    {
        $messages = [];
        $chain = $previous->chain();

        foreach ($chain as $gen) {
            if ($gen->revision_kind === 'initial') {
                // The original prompt is reconstructed from the persisted text.
                $userPart = $this->extractUserMessage($gen);
                if ($userPart !== '') {
                    $messages[] = ['role' => 'user', 'content' => $userPart];
                }
                if ($gen->output !== '') {
                    $messages[] = ['role' => 'assistant', 'content' => $gen->output];
                }
            } elseif ($gen->revision_kind === 'ai_refine') {
                if ($gen->user_instruction) {
                    $messages[] = ['role' => 'user', 'content' => $gen->user_instruction];
                }
                if ($gen->output !== '') {
                    $messages[] = ['role' => 'assistant', 'content' => $gen->output];
                }
            } elseif ($gen->revision_kind === 'manual_edit' && $gen->output !== '') {
                // Inject the manual edit as the assistant's latest answer so the
                // next AI refinement sees the lawyer's version, not the previous AI one.
                // We pop the previous assistant turn and replace with this one.
                $last = end($messages);
                if ($last && $last['role'] === 'assistant') {
                    array_pop($messages);
                }
                $messages[] = ['role' => 'assistant', 'content' => $gen->output];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $newInstruction];

        return $messages;
    }

    private function extractSystemPrompt(AiGeneration $gen): string
    {
        $root = $gen->root();
        $prompt = $root->prompt ?? '';
        $pos = strpos($prompt, "\n\n---\n\n");
        if ($pos === false) {
            return '';
        }

        return substr($prompt, 0, $pos);
    }

    private function extractUserMessage(AiGeneration $gen): string
    {
        $prompt = $gen->prompt ?? '';
        $pos = strpos($prompt, "\n\n---\n\n");
        if ($pos === false) {
            return '';
        }
        $body = substr($prompt, $pos + 7);

        // The stored format is "user: ...content...". Strip the prefix.
        if (str_starts_with($body, 'user: ')) {
            return substr($body, 6);
        }

        return $body;
    }

    /** @param  array{system:string, messages:array<int, array{role:string,content:string}>}  $prompt */
    private function serializePrompt(array $prompt): string
    {
        return $prompt['system']."\n\n---\n\n".collect($prompt['messages'])
            ->map(fn ($m) => $m['role'].': '.$m['content'])
            ->implode("\n\n");
    }
}
