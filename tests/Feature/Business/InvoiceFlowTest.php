<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Invoices\Form;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['id' => 'samir', 'name' => 'Samir']);
    tenancy()->initialize($this->tenant);

    $this->partner = User::create([
        'tenant_id' => 'samir',
        'name' => 'P',
        'email' => 'p@s.test',
        'password' => Hash::make('x'),
        'role' => UserRole::Partner->value,
        'locale' => 'ar',
    ]);

    $this->client = Client::create(['type' => 'individual', 'name' => 'X']);
});

afterEach(function () {
    tenancy()->end();
});

it('creates an invoice with lines and computes totals + 14% VAT', function () {
    Livewire::actingAs($this->partner)
        ->test(Form::class)
        ->set('client_id', $this->client->id)
        ->set('number', 'INV-001')
        ->set('issue_date', '2026-06-01')
        ->set('status', 'sent')
        ->set('lines', [
            ['description' => 'استشارة قانونية', 'quantity' => '2', 'unit_price_egp' => '500'],
            ['description' => 'صياغة عقد',   'quantity' => '1', 'unit_price_egp' => '1500'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $invoice = Invoice::first();

    // subtotal = (2 * 500) + (1 * 1500) = 2500 EGP = 250 000 piastres
    expect($invoice->subtotal_piastres)->toBe(250_000)
        ->and($invoice->tax_piastres)->toBe(35_000) // 14%
        ->and($invoice->total_piastres)->toBe(285_000)
        ->and($invoice->status)->toBe('sent');
});

it('recalculates status when a payment is recorded', function () {
    $invoice = Invoice::create([
        'client_id' => $this->client->id,
        'number' => 'INV-002',
        'issue_date' => '2026-06-01',
        'status' => 'sent',
        'currency' => 'EGP',
    ]);

    // One line for 1000 EGP → 100 000 piastres subtotal, 14 000 tax, 114 000 total.
    $invoice->lines()->create([
        'description' => 'استشارة',
        'quantity' => 1,
        'unit_price_piastres' => 100_000,
        'amount_piastres' => 100_000,
    ]);
    $invoice->recalculate();

    Payment::create([
        'invoice_id' => $invoice->id,
        'amount_piastres' => 50_000,
        'paid_at' => '2026-06-05',
        'method' => 'bank_transfer',
    ]);

    $invoice->recalculate();
    expect($invoice->fresh()->status)->toBe('partly_paid')
        ->and($invoice->fresh()->paid_piastres)->toBe(50_000);

    Payment::create([
        'invoice_id' => $invoice->id,
        'amount_piastres' => 64_000,
        'paid_at' => '2026-06-12',
        'method' => 'bank_transfer',
    ]);

    $invoice->recalculate();
    expect($invoice->fresh()->status)->toBe('paid')
        ->and($invoice->fresh()->paid_piastres)->toBe(114_000);
});

it('enforces unique invoice number per tenant', function () {
    Invoice::create([
        'client_id' => $this->client->id,
        'number' => 'DUP',
        'issue_date' => '2026-06-01',
        'currency' => 'EGP',
    ]);

    expect(fn () => Invoice::create([
        'client_id' => $this->client->id,
        'number' => 'DUP',
        'issue_date' => '2026-06-02',
        'currency' => 'EGP',
    ]))->toThrow(QueryException::class);
});
