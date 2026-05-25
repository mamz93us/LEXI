<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Computed]
    public function companies(): Collection
    {
        return Company::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name_ar', 'like', "%{$this->search}%")
                        ->orWhere('commercial_register_no', 'like', "%{$this->search}%");
                });
            })
            ->withCount(['formationSteps as pending_steps_count' => fn ($q) => $q->where('status', '!=', 'done')])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.companies.index');
    }
}
