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

        $this->silenceTempnamFallbackNotice();
    }

    /**
     * PHP emits an E_NOTICE when `tempnam()` falls back to the system
     * temp directory (because the requested directory is unwritable by
     * the FPM user). Laravel's HandleExceptions converts that benign
     * notice into a fatal ErrorException — and Laravel's AliasLoader
     * happens to call `tempnam()` while generating real-time facades
     * (which Livewire 3 uses for `Facades\Livewire\Features\
     * SupportFileUploads\FileUploadController`). The result: any file
     * upload on a fresh host with sub-optimal storage perms 500s with
     * a cryptic stack trace.
     *
     * We install a tiny shim handler that silently swallows JUST that
     * specific notice and lets everything else fall through to
     * Laravel's normal exception conversion. The tempnam call itself
     * still succeeds (the file IS created — in /tmp instead of our
     * preferred cache dir), so the request completes. The system temp
     * fallback is fine here because the facade stub is regenerated on
     * every request that needs it anyway.
     *
     * This is a safety net — the right long-term fix is still to make
     * sure storage/framework/cache is writable by the FPM user. But
     * this stops a single permissions edge case from breaking uploads
     * across every deploy target.
     */
    private function silenceTempnamFallbackNotice(): void
    {
        $previous = set_error_handler(function (int $severity, string $message, string $file, int $line) use (&$previous) {
            $isTempnamFallback = $severity === E_NOTICE
                && str_contains($message, 'tempnam(): file created in the system');

            if ($isTempnamFallback) {
                return true; // swallow, do not convert to exception
            }

            // Delegate to whatever handler we replaced (typically Laravel's
            // HandleExceptions, which converts to ErrorException).
            return $previous ? $previous($severity, $message, $file, $line) : false;
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
