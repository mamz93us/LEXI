<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Tenant;

beforeEach(function () {
    Tenant::create(['id' => 'firma', 'name' => 'Firm A']);
    Tenant::create(['id' => 'firmb', 'name' => 'Firm B']);
});

afterEach(function () {
    tenancy()->end();
});

it('scopes clients to the active tenant', function () {
    tenancy()->initialize(Tenant::find('firma'));
    Client::create(['type' => 'individual', 'name' => 'Client A1']);
    tenancy()->end();

    tenancy()->initialize(Tenant::find('firmb'));
    Client::create(['type' => 'individual', 'name' => 'Client B1']);
    expect(Client::count())->toBe(1)
        ->and(Client::first()->name)->toBe('Client B1');
    tenancy()->end();

    tenancy()->initialize(Tenant::find('firma'));
    expect(Client::count())->toBe(1)
        ->and(Client::first()->name)->toBe('Client A1');
});

it('blocks model queries when no tenant context is active', function () {
    expect(fn () => Client::count())
        ->toThrow(RuntimeException::class, 'No active tenant context');
});

it('cannot read a foreign tenant\'s case even via direct id', function () {
    tenancy()->initialize(Tenant::find('firma'));
    $clientA = Client::create(['type' => 'individual', 'name' => 'A']);
    $caseA = LegalCase::create([
        'client_id' => $clientA->id,
        'case_number' => 'A-1',
        'title' => 'Case A',
        'status' => 'open',
    ]);
    tenancy()->end();

    tenancy()->initialize(Tenant::find('firmb'));
    expect(LegalCase::find($caseA->id))->toBeNull();
});

it('exposes an explicit escape hatch via withoutTenantScope()', function () {
    tenancy()->initialize(Tenant::find('firma'));
    Client::create(['type' => 'individual', 'name' => 'A1']);
    tenancy()->end();

    tenancy()->initialize(Tenant::find('firmb'));
    Client::create(['type' => 'individual', 'name' => 'B1']);

    // From firmb's context, withoutTenantScope() returns everything.
    expect(Client::withoutTenantScope()->count())->toBe(2);
});
