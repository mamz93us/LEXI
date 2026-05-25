<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\AiGeneration;
use App\Models\ClauseVersion;
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
    ): AiGeneration {
        $retrievedChunks = $this->retriever->retrieve($userIntent);
        $prompt = $this->assembler->assemble(
            userIntent: $userIntent,
            retrievedChunks: $retrievedChunks,
            verbatimClauses: $verbatimClauses,
            filledData: $filledData,
        );

        // Audit row is created BEFORE the LLM call so a failed call still
        // leaves a trace of what was sent.
        $generation = AiGeneration::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'model' => config('lexa.anthropic.model'),
            'prompt' => $prompt['system']."\n\n---\n\n".collect($prompt['messages'])
                ->map(fn ($m) => $m['role'].': '.$m['content'])
                ->implode("\n\n"),
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
}
