<?php

declare(strict_types=1);

namespace App\Livewire\Hearings;

use App\Models\Hearing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url(as: 'when')]
    public string $when = 'upcoming';

    #[Computed]
    public function hearings(): Collection
    {
        $query = Hearing::query()->with('case', 'court', 'requests.requestType');

        match ($this->when) {
            'past' => $query->where('session_date', '<', now())->orderByDesc('session_date'),
            'upcoming' => $query->where('session_date', '>=', now())->orderBy('session_date'),
            default => $query->orderByDesc('session_date'),
        };

        return $query->limit(200)->get();
    }

    public function render(): View
    {
        return view('livewire.hearings.index');
    }
}
