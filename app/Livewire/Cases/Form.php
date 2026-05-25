<?php

declare(strict_types=1);

namespace App\Livewire\Cases;

use App\Models\Client;
use App\Models\LegalCase;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?LegalCase $case = null;

    public ?int $client_id = null;

    public string $case_number = '';

    public string $title = '';

    public string $status = 'open';

    public ?int $dispute_value_piastres = null;

    public ?string $notes = null;

    public function mount(?LegalCase $case = null): void
    {
        if ($case && $case->exists) {
            $this->authorize('update', $case);

            $this->case = $case;
            $this->client_id = $case->client_id;
            $this->case_number = $case->case_number;
            $this->title = $case->title;
            $this->status = $case->status;
            $this->dispute_value_piastres = $case->dispute_value_piastres;
            $this->notes = $case->notes;
        } else {
            $this->authorize('create', LegalCase::class);
        }
    }

    #[Computed]
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'case_number' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['open', 'on_hold', 'closed'])],
            'dispute_value_piastres' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->case) {
            $this->case->update($data);
        } else {
            LegalCase::create($data);
        }

        return $this->redirectRoute('cases.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cases.form');
    }
}
