<?php

declare(strict_types=1);

namespace App\Livewire\Calendar;

use App\Models\Deadline;
use App\Models\Hearing;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $mode = 'firm'; // firm | mine | today

    /**
     * @return array<string, array{date: CarbonImmutable, hearings: array, deadlines: array}>
     */
    #[Computed]
    public function days(): array
    {
        $from = CarbonImmutable::today();
        $to = $this->mode === 'today' ? $from : $from->addDays(45);

        $hearings = Hearing::query()
            ->whereBetween('session_date', [$from, $to])
            ->when($this->mode === 'mine', fn ($q) => $q->where('attended_by', auth()->user()?->name))
            ->with(['case'])
            ->orderBy('session_date')
            ->get();

        $deadlines = Deadline::query()
            ->whereBetween('due_date', [$from, $to])
            ->where('status', 'open')
            ->orderBy('due_date')
            ->get();

        $grid = [];
        foreach ($hearings as $hearing) {
            $key = $hearing->session_date->format('Y-m-d');
            $grid[$key]['date'] = $hearing->session_date->toImmutable();
            $grid[$key]['hearings'][] = $hearing;
            $grid[$key]['deadlines'] ??= [];
        }
        foreach ($deadlines as $deadline) {
            $key = $deadline->due_date->format('Y-m-d');
            $grid[$key]['date'] = $deadline->due_date->toImmutable();
            $grid[$key]['deadlines'][] = $deadline;
            $grid[$key]['hearings'] ??= [];
        }

        ksort($grid);

        return $grid;
    }

    public function render(): View
    {
        return view('livewire.calendar.index');
    }
}
