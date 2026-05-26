<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Hearing;
use App\Models\Judgment;
use App\Models\LegalCase;
use App\Observers\JudgmentObserver;
use App\Services\Ai\AnthropicClient;
use App\Services\Arabic\ArabicNormalizer;
use App\Services\Embeddings\EmbeddingDriverManager;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArabicNormalizer::class);
        $this->app->singleton(EmbeddingDriverManager::class);

        $this->app->singleton(AnthropicClient::class, function ($app) {
            return new AnthropicClient(
                http: $app->make(HttpClient::class),
                apiKey: (string) config('lexa.anthropic.api_key', ''),
                model: (string) config('lexa.anthropic.model', 'claude-opus-4-7'),
                zeroRetention: (bool) config('lexa.anthropic.zero_retention', true),
                maxTokens: (int) config('lexa.anthropic.max_tokens', 4096),
            );
        });
    }

    public function boot(): void
    {
        // In production we are always behind cPanel's HTTPS proxy. Force
        // every generated URL to https so OAuth / Stripe / mail links don't
        // fall back to plain http.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Route model bindings for params that don't match a model class name.
        Route::model('case', LegalCase::class);
        Route::model('client', Client::class);
        Route::model('hearing', Hearing::class);
        Route::model('judgment', Judgment::class);

        // Observers
        Judgment::observe(JudgmentObserver::class);
    }
}
