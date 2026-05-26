<?php

declare(strict_types=1);

namespace App\Livewire\IpAssets;

use App\Models\Client;
use App\Models\Company;
use App\Models\IpAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?IpAsset $asset = null;

    public ?int $company_id = null;

    public ?int $client_id = null;

    public string $asset_type = 'trademark';

    public string $title = '';

    public ?string $classification = null;

    public ?string $office_serial = null;

    public ?string $filed_on = null;

    public ?string $granted_on = null;

    public ?string $renewal_date = null;

    public string $status = 'active';

    public ?string $notes = null;

    public function mount(?IpAsset $asset = null): void
    {
        if ($asset && $asset->exists) {
            $this->asset = $asset;
            foreach (['company_id', 'client_id', 'asset_type', 'title', 'classification', 'office_serial', 'status', 'notes'] as $f) {
                $this->{$f} = $asset->{$f};
            }
            $this->filed_on = $asset->filed_on?->toDateString();
            $this->granted_on = $asset->granted_on?->toDateString();
            $this->renewal_date = $asset->renewal_date?->toDateString();
        }
    }

    #[Computed]
    public function companies()
    {
        return Company::query()->orderBy('name')->get();
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->orderBy('name')->get();
    }

    protected function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'asset_type' => ['required', Rule::in(['trademark', 'patent', 'copyright'])],
            'title' => ['required', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:64'],
            'office_serial' => ['nullable', 'string', 'max:64'],
            'filed_on' => ['nullable', 'date'],
            'granted_on' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'active', 'expired', 'abandoned'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->asset) {
            $this->asset->update($data);
        } else {
            IpAsset::create($data);
        }

        return $this->redirectRoute('ip-assets.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.ip-assets.form');
    }
}
