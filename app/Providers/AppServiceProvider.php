<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Hearing;
use App\Models\Judgment;
use App\Models\LegalCase;
use App\Observers\JudgmentObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Route model bindings for params that don't match a model class name.
        Route::model('case', LegalCase::class);
        Route::model('client', Client::class);
        Route::model('hearing', Hearing::class);
        Route::model('judgment', Judgment::class);

        // Observers
        Judgment::observe(JudgmentObserver::class);
    }
}
