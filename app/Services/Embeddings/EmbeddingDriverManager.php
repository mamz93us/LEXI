<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpClient;
use RuntimeException;

/**
 * Resolves the configured EmbeddingDriver at call time, reading from
 * the per-tenant Settings table with `.env` config as fallback. Firms
 * can change driver / key / model from Settings → AI without a deploy.
 */
final class EmbeddingDriverManager
{
    public function __construct(
        private readonly Application $app,
        private readonly SettingsService $settings,
    ) {}

    public function driver(?string $name = null): EmbeddingDriver
    {
        $name = $name ?? (string) $this->settings->get(
            'embeddings',
            'driver',
            config('lexa.embeddings.driver', 'null'),
        );

        $dimension = (int) $this->settings->get(
            'embeddings',
            'dimension',
            config('lexa.embeddings.dimension', 1024),
        );

        return match ($name) {
            'null', '' => new NullEmbeddingDriver($dimension),
            'cohere' => new CohereEmbeddingDriver(
                http: $this->app->make(HttpClient::class),
                apiKey: (string) $this->settings->get('embeddings', 'api_key', config('lexa.embeddings.api_key', '')),
                model: (string) $this->settings->get('embeddings', 'model', config('lexa.embeddings.model', 'embed-multilingual-v3.0')),
                dimension: $dimension,
            ),
            default => throw new RuntimeException("Unknown embedding driver [{$name}]"),
        };
    }
}
