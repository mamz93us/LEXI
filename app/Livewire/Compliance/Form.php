<?php

declare(strict_types=1);

namespace App\Livewire\Compliance;

use App\Models\Company;
use App\Models\ComplianceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?ComplianceItem $item = null;

    public ?int $company_id = null;

    public string $type = 'cr_renewal';

    public ?string $title = null;

    public string $due_date = '';

    public ?string $recurrence = null;

    public ?string $notes = null;

    public function mount(?ComplianceItem $item = null): void
    {
        if ($item && $item->exists) {
            $this->item = $item;
            $this->company_id = $item->company_id;
            $this->type = $item->type;
            $this->title = $item->title;
            $this->due_date = $item->due_date->toDateString();
            $this->recurrence = $item->recurrence;
            $this->notes = $item->notes;
        }
    }

    #[Computed]
    public function companies()
    {
        return Company::query()->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'type' => ['required', Rule::in(['cr_renewal', 'vat', 'tax', 'social_insurance', 'agm', 'auditor', 'license', 'other'])],
            'title' => ['nullable', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'recurrence' => ['nullable', Rule::in(['monthly', 'quarterly', 'annual', 'one_off'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->item) {
            $this->item->update($data);
        } else {
            ComplianceItem::create($data + ['status' => 'open']);
        }

        return $this->redirectRoute('compliance.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.compliance.form');
    }
}
