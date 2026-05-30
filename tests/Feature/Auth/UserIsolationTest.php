<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    Tenant::create(['id' => 'firma', 'name' => 'Firm A']);
    Tenant::create(['id' => 'firmb', 'name' => 'Firm B']);
});

afterEach(function () {
    tenancy()->end();
});

function makeUser(string $tenantId, UserRole $role, string $email): User
{
    return User::create([
        'tenant_id' => $tenantId,
        'name' => $email,
        'email' => $email,
        'password' => bcrypt('secret-pass-123'),
        'role' => $role,
        'is_active' => true,
    ]);
}

it('forCurrentTenant scopes the user list to the active firm', function () {
    makeUser('firma', UserRole::Partner, 'a-partner@x.test');
    makeUser('firma', UserRole::Associate, 'a-assoc@x.test');
    makeUser('firmb', UserRole::Partner, 'b-partner@x.test');

    tenancy()->initialize(Tenant::find('firma'));
    expect(User::query()->forCurrentTenant()->count())->toBe(2);

    tenancy()->end();
    tenancy()->initialize(Tenant::find('firmb'));
    expect(User::query()->forCurrentTenant()->count())->toBe(1)
        ->and(User::query()->forCurrentTenant()->first()->email)->toBe('b-partner@x.test');
});

it('UserPolicy denies a partner acting on another firms user', function () {
    $partnerA = makeUser('firma', UserRole::Partner, 'pa@x.test');
    $userB = makeUser('firmb', UserRole::Associate, 'ub@x.test');

    tenancy()->initialize(Tenant::find('firma'));

    expect($partnerA->can('view', $userB))->toBeFalse()
        ->and($partnerA->can('update', $userB))->toBeFalse()
        ->and($partnerA->can('deactivate', $userB))->toBeFalse();
});

it('UserPolicy allows a partner to manage same-firm users', function () {
    $partnerA = makeUser('firma', UserRole::Partner, 'pa2@x.test');
    $assocA = makeUser('firma', UserRole::Associate, 'aa2@x.test');

    tenancy()->initialize(Tenant::find('firma'));

    expect($partnerA->can('view', $assocA))->toBeTrue()
        ->and($partnerA->can('update', $assocA))->toBeTrue()
        ->and($partnerA->can('deactivate', $assocA))->toBeTrue();
});

it('UserPolicy blocks a non-manager from managing others but allows self-edit', function () {
    $assocA = makeUser('firma', UserRole::Associate, 'self@x.test');
    $otherA = makeUser('firma', UserRole::Associate, 'other@x.test');

    tenancy()->initialize(Tenant::find('firma'));

    expect($assocA->can('viewAny', User::class))->toBeFalse()
        ->and($assocA->can('update', $otherA))->toBeFalse()
        ->and($assocA->can('update', $assocA))->toBeTrue();   // self
});

it('refuses to deactivate the last active partner', function () {
    $onlyPartner = makeUser('firma', UserRole::Partner, 'only@x.test');
    $admin = makeUser('firma', UserRole::Admin, 'admin@x.test');

    tenancy()->initialize(Tenant::find('firma'));

    // Admin can normally manage, but not deactivate the last partner.
    expect($admin->can('deactivate', $onlyPartner))->toBeFalse();

    // Add a second partner → now the first can be deactivated.
    makeUser('firma', UserRole::Partner, 'second@x.test');
    expect($admin->can('deactivate', $onlyPartner))->toBeTrue();
});
