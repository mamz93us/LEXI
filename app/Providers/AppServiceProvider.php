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
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Auto-resolution works for all of these — concrete classes with
        // typed constructor deps. Bound as singletons so the request-scoped
        // setting cache survives across the request.
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(ArabicNormalizer::class);
        $this->app->singleton(EmbeddingDriverManager::class);
        $this->app->singleton(AnthropicClient::class);
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

        // Livewire AJAX endpoints (`/livewire/update`, file uploads, etc.) are
        // registered globally by the Livewire service provider, OUTSIDE our
        // tenant route group. Without tenancy middleware on them, every
        // Livewire round-trip runs with `tenant()` == null — which silently
        // breaks anything that uses the active tenant (login auth scope,
        // BelongsToTenant scope, etc.). Re-register the update route with
        // the same identification middleware our tenant routes use.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    InitializeTenancyByDomainOrSubdomain::class,
                ]);
        });
    }
}
