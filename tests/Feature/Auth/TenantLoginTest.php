<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->samir = Tenant::create(['id' => 'samir', 'name' => 'Samir Group Legal']);
    $this->samir->domains()->create(['domain' => 'samir.lexa.test']);

    $this->demo = Tenant::create(['id' => 'demo', 'name' => 'Demo Firm']);
    $this->demo->domains()->create(['domain' => 'demo.lexa.test']);

    User::create([
        'tenant_id' => 'samir',
        'name' => 'Samir Partner',
        'email' => 'partner@example.test',
        'password' => Hash::make('secret'),
        'role' => UserRole::Partner->value,
        'locale' => 'ar',
    ]);

    User::create([
        'tenant_id' => 'demo',
        'name' => 'Demo Partner',
        'email' => 'partner@example.test',
        'password' => Hash::make('secret'),
        'role' => UserRole::Partner->value,
        'locale' => 'ar',
    ]);
});

afterEach(function () {
    tenancy()->end();
    Auth::logout();
});

it('authenticates the samir partner when samir is the active tenant', function () {
    tenancy()->initialize($this->samir);

    $ok = Auth::attempt([
        'email' => 'partner@example.test',
        'password' => 'secret',
        'tenant_id' => tenant('id'),
    ]);

    expect($ok)->toBeTrue()
        ->and(Auth::user()->tenant_id)->toBe('samir');
});

it('rejects wrong password even when tenant is correct', function () {
    tenancy()->initialize($this->samir);

    $ok = Auth::attempt([
        'email' => 'partner@example.test',
        'password' => 'wrong-password',
        'tenant_id' => tenant('id'),
    ]);

    expect($ok)->toBeFalse()
        ->and(Auth::check())->toBeFalse();
});

it('authenticates the demo user when demo is the active tenant', function () {
    // Same email exists at both firms — auth must return the one matching tenant.
    tenancy()->initialize($this->demo);

    $ok = Auth::attempt([
        'email' => 'partner@example.test',
        'password' => 'secret',
        'tenant_id' => tenant('id'),
    ]);

    expect($ok)->toBeTrue()
        ->and(Auth::user()->tenant_id)->toBe('demo');
});
