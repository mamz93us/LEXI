<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\ContractEmbedding;
use App\Services\Arabic\ArabicNormalizer;
use App\Services\Embeddings\EmbeddingDriverManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tenant-scoped top-k similarity search over `contract_embeddings`.
 *
 * Defense in depth: tenant_id is filtered both by the global scope (from
 * BelongsToTenant on ContractEmbedding) AND by a literal WHERE clause in
 * the raw SQL — if the scope is ever bypassed, the SQL still rejects
 * foreign vectors.
 */
final class RagRetrievalService
{
    public function __construct(
        private readonly ArabicNormalizer $normalizer,
        private readonly EmbeddingDriverManager $embeddings,
    ) {}

    /**
     * @return Collection<int, array{id:int, document_id:?int, chunk_text:string, metadata:array, distance:float}>
     */
    public function retrieve(string $query, int $topK = 0): Collection
    {
        $tenantId = tenant('id');
        if (! $tenantId) {
            throw new RuntimeException('RagRetrievalService called without an active tenant.');
        }

        $topK = $topK > 0 ? $topK : (int) config('lexa.rag.top_k', 8);

        if (DB::getDriverName() !== 'pgsql') {
            // Without pgvector, fall back to lexical LIKE search on the
            // normalised text. Good enough for dev / tests; production
            // must be on Postgres with pgvector for semantic recall.
            return $this->lexicalFallback($query, $tenantId, $topK);
        }

        $normalized = $this->normalizer->normalize($query);
        $vector = $this->embeddings->driver()->embed($normalized);
        $vectorLiteral = '['.implode(',', $vector).']';

        $distance = match ((string) config('lexa.rag.distance', 'cosine')) {
            'cosine' => '<=>',
            'l2' => '<->',
            'inner' => '<#>',
            default => '<=>',
        };

        $rows = DB::select(
            "SELECT id, document_id, chunk_text, metadata,
                    embedding {$distance} ?::vector AS distance
             FROM contract_embeddings
             WHERE tenant_id = ?
             ORDER BY distance ASC
             LIMIT ?",
            [$vectorLiteral, $tenantId, $topK],
        );

        return collect($rows)->map(fn ($row) => [
            'id' => (int) $row->id,
            'document_id' => $row->document_id ? (int) $row->document_id : null,
            'chunk_text' => $row->chunk_text,
            'metadata' => json_decode($row->metadata ?? '{}', true) ?: [],
            'distance' => (float) $row->distance,
        ]);
    }

    /**
     * @return Collection<int, array{id:int, document_id:?int, chunk_text:string, metadata:array, distance:float}>
     */
    private function lexicalFallback(string $query, string $tenantId, int $topK): Collection
    {
        $normalized = $this->normalizer->normalize($query);
        $terms = collect(preg_split('/\s+/u', $normalized))
            ->filter(fn ($t) => mb_strlen($t) > 2);

        return ContractEmbedding::query()
            ->where('tenant_id', $tenantId)
            ->when($terms->isNotEmpty(), function ($q) use ($terms) {
                $q->where(function ($qq) use ($terms) {
                    foreach ($terms as $term) {
                        $qq->orWhere('chunk_text', 'like', "%{$term}%");
                    }
                });
            })
            ->limit($topK)
            ->get()
            ->map(fn (ContractEmbedding $row) => [
                'id' => $row->id,
                'document_id' => $row->document_id,
                'chunk_text' => $row->chunk_text,
                'metadata' => $row->metadata ?? [],
                'distance' => 1.0, // distance is meaningless in lexical mode
            ]);
    }
}
