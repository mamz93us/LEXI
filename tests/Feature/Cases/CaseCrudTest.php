<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Cases\Form;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['id' => 'samir', 'name' => 'Samir Group Legal']);
    $this->tenant->domains()->create(['domain' => 'samir.lexa.test']);
    tenancy()->initialize($this->tenant);

    $this->partner = User::create([
        'tenant_id' => 'samir',
        'name' => 'Partner',
        'email' => 'partner@samir.test',
        'password' => Hash::make('secret'),
        'role' => UserRole::Partner->value,
        'locale' => 'ar',
    ]);

    $this->client = Client::create(['type' => 'individual', 'name' => 'Mr. X']);
});

afterEach(function () {
    tenancy()->end();
});

it('creates a case', function () {
    Livewire::actingAs($this->partner)
        ->test(Form::class)
        ->set('client_id', $this->client->id)
        ->set('case_number', 'CIV-2026-001')
        ->set('title', 'دعوى مدنية')
        ->set('status', 'open')
        ->call('save')
        ->assertHasNoErrors();

    expect(LegalCase::count())->toBe(1)
        ->and(LegalCase::first()->case_number)->toBe('CIV-2026-001')
        ->and(LegalCase::first()->tenant_id)->toBe('samir');
});

it('requires a case_number and client_id', function () {
    Livewire::actingAs($this->partner)
        ->test(Form::class)
        ->set('case_number', '')
        ->set('client_id', null)
        ->call('save')
        ->assertHasErrors(['case_number' => 'required'])
        ->assertHasErrors(['client_id' => 'required']);
});

it('enforces unique case_number per tenant', function () {
    LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'DUP-1',
        'title' => 'First',
        'status' => 'open',
    ]);

    expect(fn () => LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'DUP-1',
        'title' => 'Second',
        'status' => 'open',
    ]))->toThrow(QueryException::class);
});
