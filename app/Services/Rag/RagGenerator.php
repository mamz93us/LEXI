<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Jobs\RunAiGenerationJob;
use App\Models\AiGeneration;
use App\Models\ClauseVersion;
use App\Models\TemplateVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Orchestrates the RAG flow: retrieve → assemble → queue an LLM call →
 * return immediately. The actual Anthropic call runs in a queued
 * `RunAiGenerationJob`. The lawyer's browser sees a `pending` row and
 * polls the Detail page until the job finishes.
 *
 * Why async: long Arabic contracts can take 30–120 seconds for Claude to
 * draft at 8k tokens; doing that inside the Livewire request risks hitting
 * PHP-FPM / proxy timeouts and leaves the UI hung. Queueing decouples the
 * LLM latency from the web request.
 *
 * The persisted `AiGeneration` row is the audit trail required by
 * CLAUDE.md §6.5 — it's created BEFORE the LLM call so a failed call still
 * leaves a trace.
 */
final class RagGenerator
{
    public function __construct(
        private readonly RagRetrievalService $retriever,
        private readonly PromptAssembler $assembler,
    ) {}

    /**
     * @param  array<string, mixed>  $filledData
     * @param  Collection<int, ClauseVersion>  $verbatimClauses
     *
     * `$subject` is the polymorphic anchor for the audit row (LegalCase /
     * Template / Document …). For from-scratch drafts that aren't tied to
     * any existing model, pass null — the row still carries tenant_id via
     * BelongsToTenant, which is the real isolation key.
     *
     * Subject must use an integer primary key, or null. Don't pass the
     * Tenant model — its primary key is a slug string, which won't fit
     * into the `ai_generations.subject_id bigint` column.
     */
    public function draft(
        ?Model $subject,
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

        $generation = AiGeneration::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'parent_id' => null,
            'revision_kind' => 'initial',
            'model' => config('lexa.anthropic.model'),
            'prompt' => $this->serializePrompt($prompt),
            'retrieved_chunk_ids' => $retrievedChunks->pluck('id')->all(),
            'output' => '',
            'status' => 'pending',
        ]);

        RunAiGenerationJob::dispatch(
            $generation->id,
            $prompt['system'],
            $prompt['messages'],
        );

        return $generation;
    }

    /**
     * Conversational refinement — same async pattern. The previous chain
     * is replayed as Claude conversation context, plus the new user
     * instruction. A new AiGeneration row is created with `parent_id`
     * pointing at the previous so the chain is preserved.
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
            'status' => 'pending',
        ]);

        RunAiGenerationJob::dispatch(
            $generation->id,
            $systemPrompt,
            $chainMessages,
        );

        return $generation;
    }

    /**
     * Manual edit by the lawyer. No Claude call — synchronous, returns
     * a finished `draft` row immediately.
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
        if ($pos !== false) {
            $system = trim(substr($prompt, 0, $pos));
            if ($system !== '') {
                return $system;
            }
        }

        // Fallback: the root prompt was a manual-edit (no system block) or
        // an unexpected format. We MUST NOT send Claude an empty system
        // prompt — that drops the "never fabricate article numbers /
        // statutory citations" guardrail (CLAUDE.md §6.3). Re-assemble the
        // canonical from-scratch system prompt instead.
        return $this->assembler->assemble(
            userIntent: '',
            retrievedChunks: collect(),
            verbatimClauses: collect(),
            filledData: [],
            template: null,
        )['system'];
    }

    private function extractUserMessage(AiGeneration $gen): string
    {
        $prompt = $gen->prompt ?? '';
        $pos = strpos($prompt, "\n\n---\n\n");
        if ($pos === false) {
            return '';
        }
        $body = substr($prompt, $pos + 7);

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
