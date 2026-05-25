<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central (landlord) routes
|--------------------------------------------------------------------------
| These run on the bare central domain (e.g. lexa.test, localhost).
| Auth, dashboard and tenant-scoped routes live in routes/tenant.php.
*/

Route::view('/', 'central.landing')->name('central.landing');
