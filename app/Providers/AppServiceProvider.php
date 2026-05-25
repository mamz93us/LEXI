<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\LegalCase;
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
        // {case} → LegalCase (the PHP keyword forces a non-default model name)
        Route::model('case', LegalCase::class);
        Route::model('client', Client::class);
    }
}
