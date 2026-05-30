<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyFormationStep;
use App\Models\Shareholder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Company detail with inline management of the two previously-orphan
 * children: shareholders (ownership %) and the formation-step checklist.
 */
#[Layout('layouts.app')]
class Detail extends Component
{
    public Company $company;

    // --- new shareholder form ---
    public ?int $sh_client_id = null;

    public string $sh_display_name = '';

    public string $sh_ownership_pct = '';

    // --- new formation step form ---
    public string $fs_title = '';

    public ?string $fs_authority = null;

    public string $fs_status = 'pending';

    public ?string $fs_expected_date = null;

    public function mount(Company $company): void
    {
        $this->company = $company;
        $this->company->recordView();
    }

    #[Computed]
    public function clientsList(): Collection
    {
        return Client::query()->orderBy('name_ar')->orderBy('name')->get(['id', 'name', 'name_ar']);
    }

    #[Computed]
    public function shareholders(): Collection
    {
        return $this->company->shareholders()->orderByDesc('ownership_pct')->get();
    }

    #[Computed]
    public function steps(): Collection
    {
        return $this->company->formationSteps()->orderBy('step_order')->orderBy('id')->get();
    }

    /** Total assigned ownership — surfaced so the lawyer can sanity-check 100%. */
    #[Computed]
    public function ownershipTotal(): float
    {
        return (float) $this->company->shareholders()->sum('ownership_pct');
    }

    public function addShareholder(): void
    {
        $data = $this->validate([
            'sh_client_id' => ['nullable', 'integer'],
            'sh_display_name' => ['required_without:sh_client_id', 'nullable', 'string', 'max:255'],
            'sh_ownership_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $name = $this->sh_display_name;
        if (! $name && $this->sh_client_id) {
            $client = $this->clientsList->firstWhere('id', (int) $this->sh_client_id);
            $name = $client?->name_ar ?: $client?->name ?: 'مساهم';
        }

        Shareholder::create([
            'company_id' => $this->company->id,
            'client_id' => $this->sh_client_id ?: null,
            'display_name' => $name,
            'ownership_pct' => (float) $data['sh_ownership_pct'],
            'effective_from' => now()->toDateString(),
        ]);

        $this->reset('sh_client_id', 'sh_display_name', 'sh_ownership_pct');
        unset($this->shareholders, $this->ownershipTotal);
    }

    public function removeShareholder(int $id): void
    {
        $this->company->shareholders()->whereKey($id)->delete();
        unset($this->shareholders, $this->ownershipTotal);
    }

    public function addStep(): void
    {
        $data = $this->validate([
            'fs_title' => ['required', 'string', 'max:255'],
            'fs_authority' => ['nullable', 'string', 'max:255'],
            'fs_status' => ['required', 'in:pending,in_progress,done,blocked'],
            'fs_expected_date' => ['nullable', 'date'],
        ]);

        $nextOrder = ((int) $this->company->formationSteps()->max('step_order')) + 1;

        CompanyFormationStep::create([
            'company_id' => $this->company->id,
            'step_order' => $nextOrder,
            'title' => $data['fs_title'],
            'authority' => $data['fs_authority'],
            'status' => $data['fs_status'],
            'expected_date' => $data['fs_expected_date'] ?: null,
        ]);

        $this->reset('fs_title', 'fs_authority', 'fs_status', 'fs_expected_date');
        $this->fs_status = 'pending';
        unset($this->steps);
    }

    public function setStepStatus(int $id, string $status): void
    {
        if (! in_array($status, ['pending', 'in_progress', 'done', 'blocked'], true)) {
            return;
        }
        $step = $this->company->formationSteps()->whereKey($id)->first();
        if ($step) {
            $step->update([
                'status' => $status,
                'actual_date' => $status === 'done' ? now()->toDateString() : $step->actual_date,
            ]);
        }
        unset($this->steps);
    }

    public function removeStep(int $id): void
    {
        $this->company->formationSteps()->whereKey($id)->delete();
        unset($this->steps);
    }

    public function render(): View
    {
        return view('livewire.companies.detail');
    }
}
