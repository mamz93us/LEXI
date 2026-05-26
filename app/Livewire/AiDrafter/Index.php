<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Services\Documents\ReviewWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Computed]
    public function generations(): Collection
    {
        return AiGeneration::query()
            ->with('reviewer')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function approve(int $id, ReviewWorkflow $workflow): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Partner, UserRole::Admin], true)) {
            session()->flash('error', 'Only partners or admins can approve drafts.');

            return;
        }
        $gen = AiGeneration::query()->findOrFail($id);
        $workflow->approve($gen, $user);
    }

    public function reject(int $id, ReviewWorkflow $workflow): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $gen = AiGeneration::query()->findOrFail($id);
        $workflow->reject($gen, $user);
    }

    public function render(): View
    {
        return view('livewire.ai-drafter.index');
    }
}
