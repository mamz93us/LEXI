<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Invoice $invoice = null;

    public ?int $client_id = null;

    public string $number = '';

    public string $issue_date = '';

    public ?string $due_date = null;

    public string $status = 'draft';

    public ?string $notes = null;

    /** @var array<int,array{description:string, quantity:string, unit_price_egp:string}> */
    public array $lines = [];

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice && $invoice->exists) {
            $this->invoice = $invoice->load('lines');
            $this->client_id = $invoice->client_id;
            $this->number = $invoice->number;
            $this->issue_date = $invoice->issue_date->toDateString();
            $this->due_date = $invoice->due_date?->toDateString();
            $this->status = $invoice->status;
            $this->notes = $invoice->notes;
            $this->lines = $invoice->lines
                ->map(fn (InvoiceLine $l) => [
                    'description' => $l->description,
                    'quantity' => (string) $l->quantity,
                    'unit_price_egp' => (string) intdiv($l->unit_price_piastres, 100),
                ])
                ->all();
        } else {
            $this->issue_date = now()->toDateString();
            $this->number = 'INV-'.now()->format('YmdHis');
            $this->lines = [['description' => '', 'quantity' => '1', 'unit_price_egp' => '0']];
        }
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->orderBy('name')->get();
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'quantity' => '1', 'unit_price_egp' => '0'];
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'number' => ['required', 'string', 'max:64'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', Rule::in(['draft', 'sent', 'partly_paid', 'paid', 'void'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price_egp' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        if (count($data['lines']) === 0) {
            throw ValidationException::withMessages(['lines' => 'Add at least one line.']);
        }

        $invoice = $this->invoice ?? Invoice::query()->newModelInstance();
        $invoice->fill([
            'client_id' => $data['client_id'],
            'number' => $data['number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?: null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'currency' => 'EGP',
        ])->save();

        $invoice->lines()->delete();
        foreach ($data['lines'] as $line) {
            $unitPiastres = (int) round(((float) $line['unit_price_egp']) * 100);
            $qty = (float) $line['quantity'];
            $invoice->lines()->create([
                'description' => $line['description'],
                'quantity' => $qty,
                'unit_price_piastres' => $unitPiastres,
                'amount_piastres' => (int) round($unitPiastres * $qty),
            ]);
        }

        $invoice->recalculate();

        return $this->redirectRoute('invoices.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.invoices.form');
    }
}
