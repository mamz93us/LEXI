<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Resolves the configured EmbeddingDriver. Bound as a singleton in
 * AppServiceProvider so the choice is locked per-request.
 */
final class EmbeddingDriverManager
{
    public function __construct(private readonly Application $app) {}

    public function driver(?string $name = null): EmbeddingDriver
    {
        $name = $name ?? (string) config('lexa.embeddings.driver', 'null');

        return match ($name) {
            'null', '' => new NullEmbeddingDriver((int) config('lexa.embeddings.dimension', 1024)),
            'cohere' => new CohereEmbeddingDriver(
                http: $this->app->make(HttpClient::class),
                apiKey: (string) config('lexa.embeddings.api_key', ''),
                model: (string) config('lexa.embeddings.model', 'embed-multilingual-v3.0'),
                dimension: (int) config('lexa.embeddings.dimension', 1024),
            ),
            default => throw new RuntimeException("Unknown embedding driver [{$name}]"),
        };
    }
}
