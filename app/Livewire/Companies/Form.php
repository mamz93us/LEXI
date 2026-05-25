<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Company $company = null;

    public ?int $client_id = null;

    public string $name = '';

    public ?string $name_ar = null;

    public string $legal_form = 'llc';

    public ?string $commercial_register_no = null;

    public ?string $tax_card_no = null;

    public ?string $gafi_file_no = null;

    public ?int $capital_egp = null;

    public string $status = 'in_formation';

    public ?string $notes = null;

    public function mount(?Company $company = null): void
    {
        if ($company && $company->exists) {
            $this->company = $company;
            $this->client_id = $company->client_id;
            $this->name = $company->name;
            $this->name_ar = $company->name_ar;
            $this->legal_form = $company->legal_form;
            $this->commercial_register_no = $company->commercial_register_no;
            $this->tax_card_no = $company->tax_card_no;
            $this->gafi_file_no = $company->gafi_file_no;
            $this->capital_egp = $company->capital_piastres
                ? intdiv($company->capital_piastres, 100)
                : null;
            $this->status = $company->status;
            $this->notes = $company->notes;
        }
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'legal_form' => ['required', Rule::in(['llc', 'jsc', 'sole', 'branch'])],
            'commercial_register_no' => ['nullable', 'string', 'max:64'],
            'tax_card_no' => ['nullable', 'string', 'max:64'],
            'gafi_file_no' => ['nullable', 'string', 'max:64'],
            'capital_egp' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'suspended', 'dissolved', 'in_formation'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        $data['capital_piastres'] = $data['capital_egp'] !== null
            ? $data['capital_egp'] * 100
            : null;
        unset($data['capital_egp']);

        if ($this->company) {
            $this->company->update($data);
        } else {
            Company::create($data);
        }

        return $this->redirectRoute('companies.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.companies.form');
    }
}
