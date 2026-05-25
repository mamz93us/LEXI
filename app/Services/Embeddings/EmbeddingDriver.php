<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

/**
 * Contract for swappable embedding providers. The dimension MUST match
 * config('lexa.embeddings.dimension') and the `vector(N)` column on
 * `contract_embeddings`. Changing the model is a breaking change that
 * requires backfilling every existing vector.
 */
interface EmbeddingDriver
{
    /**
     * Embed a single piece of normalised Arabic legal text.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array;

    /**
     * Embed many pieces of text in a single batched call when supported.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array;

    public function dimension(): int;

    public function modelName(): string;
}
