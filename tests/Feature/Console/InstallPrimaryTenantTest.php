<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

afterEach(function () {
    tenancy()->end();
});

it('creates the primary tenant, maps its domain and seats the first partner', function () {
    $this->artisan('lexa:install-primary-tenant', [
        '--slug' => 'lexa',
        '--name' => 'LEXA Production',
        '--domain' => 'lexi.deevar.cloud',
        '--partner-email' => 'partner@example.test',
        '--partner-name' => 'محمد',
        '--partner-password' => 'a-strong-password',
        '--force' => true,
    ])->assertExitCode(0);

    /** @var Tenant $tenant */
    $tenant = Tenant::query()->withoutGlobalScopes()->find('lexa');
    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('LEXA Production')
        ->and($tenant->domains->pluck('domain')->all())->toContain('lexi.deevar.cloud');

    tenancy()->initialize($tenant);
    expect(User::where('email', 'partner@example.test')->exists())->toBeTrue();
});

it('is idempotent — re-running with the same slug does not duplicate', function () {
    $args = [
        '--slug' => 'lexa',
        '--name' => 'LEXA',
        '--domain' => 'lexi.deevar.cloud',
        '--partner-email' => 'partner@example.test',
        '--partner-name' => 'محمد',
        '--partner-password' => 'a-strong-password',
        '--force' => true,
    ];

    $this->artisan('lexa:install-primary-tenant', $args)->assertExitCode(0);
    $this->artisan('lexa:install-primary-tenant', array_merge($args, ['--name' => 'LEXA renamed']))
        ->assertExitCode(0);

    expect(Tenant::query()->withoutGlobalScopes()->count())->toBe(1);
    $tenant = Tenant::query()->withoutGlobalScopes()->find('lexa');
    expect($tenant->name)->toBe('LEXA renamed')
        ->and($tenant->domains()->count())->toBe(1);

    tenancy()->initialize($tenant);
    expect(User::where('email', 'partner@example.test')->count())->toBe(1);
});

it('rejects a slug with invalid characters', function () {
    $this->artisan('lexa:install-primary-tenant', [
        '--slug' => 'Bad Slug!',
        '--name' => 'X',
        '--domain' => 'x.example.com',
        '--partner-email' => 'p@x.test',
        '--partner-name' => 'N',
        '--partner-password' => 'a-strong-password',
        '--force' => true,
    ])->assertExitCode(1);

    expect(Tenant::query()->withoutGlobalScopes()->count())->toBe(0);
});
