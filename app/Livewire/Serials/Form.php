<?php

declare(strict_types=1);

namespace App\Livewire\Serials;

use App\Models\Company;
use App\Models\LegalCase;
use App\Models\Serial;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Serial $serial = null;

    public string $serial_no = '';

    public string $document_name = '';

    public ?string $issuing_authority = null;

    public string $owner_kind = 'none';

    public ?int $owner_id = null;

    public ?int $fees_egp = null;

    public ?string $issued_at = null;

    public string $status = 'pending';

    public ?string $notes = null;

    public function mount(?Serial $serial = null): void
    {
        if ($serial && $serial->exists) {
            $this->serial = $serial;
            $this->serial_no = $serial->serial_no;
            $this->document_name = $serial->document_name;
            $this->issuing_authority = $serial->issuing_authority;
            $this->owner_kind = match ($serial->owner_type) {
                LegalCase::class => 'case',
                Company::class => 'company',
                default => 'none',
            };
            $this->owner_id = $serial->owner_id;
            $this->fees_egp = $serial->fees_piastres ? intdiv($serial->fees_piastres, 100) : null;
            $this->issued_at = $serial->issued_at?->toDateString();
            $this->status = $serial->status;
            $this->notes = $serial->notes;
        }
    }

    #[Computed]
    public function ownerOptions(): array
    {
        return match ($this->owner_kind) {
            'case' => LegalCase::query()->orderByDesc('created_at')->limit(200)->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->case_number])->all(),
            'company' => Company::query()->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name_ar ?? $c->name])->all(),
            default => [],
        };
    }

    protected function rules(): array
    {
        return [
            'serial_no' => ['required', 'string', 'max:64'],
            'document_name' => ['required', 'string', 'max:255'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'owner_kind' => ['required', Rule::in(['none', 'case', 'company'])],
            'owner_id' => ['nullable', 'integer'],
            'fees_egp' => ['nullable', 'integer', 'min:0'],
            'issued_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'issued', 'collected'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        $ownerType = match ($data['owner_kind']) {
            'case' => LegalCase::class,
            'company' => Company::class,
            default => null,
        };

        $payload = [
            'serial_no' => $data['serial_no'],
            'document_name' => $data['document_name'],
            'issuing_authority' => $data['issuing_authority'],
            'owner_type' => $ownerType,
            'owner_id' => $ownerType ? $data['owner_id'] : null,
            'fees_piastres' => $data['fees_egp'] !== null ? $data['fees_egp'] * 100 : null,
            'issued_at' => $data['issued_at'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ];

        if ($this->serial) {
            $this->serial->update($payload);
        } else {
            Serial::create($payload);
        }

        return $this->redirectRoute('serials.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.serials.form');
    }
}
