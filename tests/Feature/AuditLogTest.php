<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Tenant;

beforeEach(function () {
    Tenant::create(['id' => 'firma', 'name' => 'Firm A']);
    tenancy()->initialize(Tenant::find('firma'));
});

afterEach(function () {
    tenancy()->end();
});

it('writes a created audit entry when a client is created', function () {
    $client = Client::create(['type' => 'individual', 'name' => 'محمد']);

    $log = AuditLog::where('auditable_type', $client->getMorphClass())
        ->where('auditable_id', $client->id)
        ->where('action', 'created')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->tenant_id)->toBe('firma')
        ->and($log->after['name'])->toBe('محمد');
});

it('writes an updated entry with a before/after diff', function () {
    $client = Client::create(['type' => 'individual', 'name' => 'A']);
    $client->update(['name' => 'B']);

    $log = AuditLog::where('auditable_id', $client->id)
        ->where('action', 'updated')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->before['name'])->toBe('A')
        ->and($log->after['name'])->toBe('B');
});

it('writes a deleted entry', function () {
    $client = Client::create(['type' => 'individual', 'name' => 'Gone']);
    $id = $client->id;
    $client->delete();

    expect(
        AuditLog::where('auditable_id', $id)->where('action', 'deleted')->exists()
    )->toBeTrue();
});

it('never records sensitive fields in the diff', function () {
    // Client has no password, but verify the exclude list filters timestamps.
    $client = Client::create(['type' => 'individual', 'name' => 'X']);
    $log = AuditLog::where('auditable_id', $client->id)->where('action', 'created')->first();

    expect($log->after)->not->toHaveKey('created_at')
        ->and($log->after)->not->toHaveKey('updated_at');
});

it('scopes the audit log to the tenant', function () {
    Client::create(['type' => 'individual', 'name' => 'A-client']);
    tenancy()->end();

    Tenant::create(['id' => 'firmb', 'name' => 'Firm B']);
    tenancy()->initialize(Tenant::find('firmb'));
    Client::create(['type' => 'individual', 'name' => 'B-client']);

    expect(AuditLog::where('tenant_id', 'firmb')->count())->toBe(1)
        ->and(AuditLog::where('tenant_id', 'firma')->count())->toBe(1);
});
