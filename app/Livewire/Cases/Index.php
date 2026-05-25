<?php

declare(strict_types=1);

namespace App\Livewire\Cases;

use App\Models\LegalCase;
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

    public function delete(int $id): void
    {
        $case = LegalCase::query()->findOrFail($id);
        $this->authorize('delete', $case);
        $case->delete();
    }

    #[Computed]
    public function cases(): Collection
    {
        return LegalCase::query()
            ->with('client')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('case_number', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function render(): View
    {
        $this->authorize('viewAny', LegalCase::class);

        return view('livewire.cases.index');
    }
}
