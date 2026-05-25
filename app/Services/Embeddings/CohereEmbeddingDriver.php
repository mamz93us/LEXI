<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Cohere `embed-multilingual-v3.0` (1024-dim) — strong out-of-the-box on
 * Arabic. Final choice locked after Phase 2 retrieval-quality eval.
 *
 * Set EMBEDDINGS_DRIVER=cohere + EMBEDDINGS_API_KEY in .env. Do NOT use
 * this driver in unit tests — set EMBEDDINGS_DRIVER=null instead.
 */
final class CohereEmbeddingDriver implements EmbeddingDriver
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $apiKey,
        private readonly string $model = 'embed-multilingual-v3.0',
        private readonly int $dimension = 1024,
    ) {}

    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts): array
    {
        $this->assertConfigured();

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.cohere.ai/v1/embed', [
                'model' => $this->model,
                'input_type' => 'search_document',
                'texts' => array_values($texts),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Cohere embedding request failed: '.$response->status().' '.$response->body()
            );
        }

        return $response->json('embeddings') ?? [];
    }

    public function dimension(): int
    {
        return $this->dimension;
    }

    public function modelName(): string
    {
        return $this->model;
    }

    private function assertConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(
                'Cohere embedding driver selected but EMBEDDINGS_API_KEY is empty. '.
                'Set it in .env or switch EMBEDDINGS_DRIVER=null for offline dev.'
            );
        }
    }
}
