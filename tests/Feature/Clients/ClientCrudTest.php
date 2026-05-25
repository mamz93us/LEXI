<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Clients\Form;
use App\Livewire\Clients\Index;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
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
});

afterEach(function () {
    tenancy()->end();
});

it('renders the clients index for an authenticated partner', function () {
    Livewire::actingAs($this->partner)
        ->test(Index::class)
        ->assertStatus(200)
        // Default locale is Arabic — assert on the translated string.
        ->assertSee('العملاء');
});

it('creates a client', function () {
    Livewire::actingAs($this->partner)
        ->test(Form::class)
        ->set('type', 'individual')
        ->set('name', 'Mohamed Ali')
        ->set('name_ar', 'محمد علي')
        ->set('phone', '+201111111111')
        ->call('save')
        ->assertHasNoErrors();

    expect(Client::count())->toBe(1)
        ->and(Client::first()->tenant_id)->toBe('samir');
});

it('validates the required name', function () {
    Livewire::actingAs($this->partner)
        ->test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    expect(Client::count())->toBe(0);
});

it('updates an existing client', function () {
    $client = Client::create(['type' => 'individual', 'name' => 'Old Name']);

    Livewire::actingAs($this->partner)
        ->test(Form::class, ['client' => $client])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh()->name)->toBe('New Name');
});

it('deletes a client (partners can delete)', function () {
    $client = Client::create(['type' => 'individual', 'name' => 'Bye']);

    Livewire::actingAs($this->partner)
        ->test(Index::class)
        ->call('delete', $client->id);

    expect(Client::count())->toBe(0);
});
