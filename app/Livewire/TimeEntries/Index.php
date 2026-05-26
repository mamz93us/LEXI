<?php

declare(strict_types=1);

namespace App\Livewire\TimeEntries;

use App\Models\TimeEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url(as: 'b')]
    public string $billable = '';

    public function delete(int $id): void
    {
        TimeEntry::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function entries(): Collection
    {
        return TimeEntry::query()
            ->with('user', 'subject')
            ->when($this->billable !== '', fn ($q) => $q->where('billable', $this->billable === '1'))
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function summary(): array
    {
        $total = TimeEntry::query()->where('billable', true)->sum('minutes');
        $invoiced = TimeEntry::query()->where('billable', true)->where('invoiced', true)->sum('minutes');
        $unbilled = $total - $invoiced;

        return [
            'total_minutes' => (int) $total,
            'unbilled_minutes' => (int) $unbilled,
            'total_hours' => round($total / 60, 1),
            'unbilled_hours' => round($unbilled / 60, 1),
        ];
    }

    public function render(): View
    {
        return view('livewire.time-entries.index');
    }
}
