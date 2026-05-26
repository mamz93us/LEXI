<?php

declare(strict_types=1);

namespace App\Livewire\Proxies;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Proxy $proxy = null;

    public ?int $client_id = null;

    public string $type = 'specific';

    public ?string $notary_serial = null;

    public string $issue_date = '';

    public ?string $expiry_date = null;

    public ?string $scope = null;

    public string $status = 'valid';

    /** @var array<int, int> */
    public array $lawyer_ids = [];

    /** @var array<int, int> */
    public array $case_ids = [];

    public function mount(?Proxy $proxy = null): void
    {
        if ($proxy && $proxy->exists) {
            $this->proxy = $proxy->load('authorizedLawyers', 'cases');
            $this->client_id = $proxy->client_id;
            $this->type = $proxy->type;
            $this->notary_serial = $proxy->notary_serial;
            $this->issue_date = $proxy->issue_date->toDateString();
            $this->expiry_date = $proxy->expiry_date?->toDateString();
            $this->scope = $proxy->scope;
            $this->status = $proxy->status;
            $this->lawyer_ids = $proxy->authorizedLawyers->pluck('id')->all();
            $this->case_ids = $proxy->cases->pluck('id')->all();
        } else {
            $this->issue_date = now()->toDateString();
        }
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->orderBy('name')->get();
    }

    #[Computed]
    public function lawyers()
    {
        return User::query()->orderBy('name')->get();
    }

    #[Computed]
    public function casesList()
    {
        return LegalCase::query()->orderByDesc('created_at')->limit(200)->get();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'type' => ['required', Rule::in(['general', 'specific'])],
            'notary_serial' => ['nullable', 'string', 'max:64'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['valid', 'expiring', 'expired', 'revoked'])],
            'lawyer_ids' => ['array'],
            'lawyer_ids.*' => ['integer', 'exists:users,id'],
            'case_ids' => ['array'],
            'case_ids.*' => ['integer', 'exists:cases,id'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->proxy) {
            $this->proxy->update($data);
            $proxy = $this->proxy;
        } else {
            $proxy = Proxy::create($data);
        }

        $proxy->authorizedLawyers()->sync($this->lawyer_ids);
        $proxy->cases()->sync($this->case_ids);

        return $this->redirectRoute('proxies.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.proxies.form');
    }
}
