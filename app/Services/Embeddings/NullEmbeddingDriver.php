<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

/**
 * Returns a deterministic zero-vector. Used in tests and as the safe
 * default when no embedding provider is configured — calls succeed
 * (no exception) but RAG retrieval will only see exact matches, not
 * semantic neighbours. Switch to a real driver before shipping.
 */
final class NullEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(private readonly int $dimension = 1024) {}

    public function embed(string $text): array
    {
        return array_fill(0, $this->dimension, 0.0);
    }

    public function embedBatch(array $texts): array
    {
        return array_map(fn () => $this->embed(''), $texts);
    }

    public function dimension(): int
    {
        return $this->dimension;
    }

    public function modelName(): string
    {
        return 'null';
    }
}
