<?php

declare(strict_types=1);

namespace App\Livewire\Judgments;

use App\Models\Judgment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Computed]
    public function judgments(): Collection
    {
        return Judgment::query()
            ->with('case', 'judgmentType', 'deadlines')
            ->orderByDesc('judgment_date')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.judgments.index');
    }
}
