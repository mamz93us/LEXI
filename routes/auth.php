<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Public self-registration is DISABLED. LEXA is single-firm in
    // production; the stock Breeze register flow created users with
    // tenant_id = NULL (which the app treats as a landlord/super-admin),
    // letting anyone self-provision an unscoped account. New staff are
    // added by a partner/admin via the in-app Users screen (/users),
    // which stamps the correct tenant_id.
    //
    // To re-enable a controlled signup later (landlord-domain firm
    // onboarding that creates a Tenant + first partner), build a dedicated
    // component that sets tenant_id explicitly — do NOT just uncomment this.
    // Volt::route('register', 'pages.auth.register')->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
