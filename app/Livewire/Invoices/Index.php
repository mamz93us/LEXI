<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Computed]
    public function invoices(): Collection
    {
        return Invoice::query()
            ->with('client')
            ->orderByDesc('issue_date')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function totalsPiastres(): array
    {
        $rows = Invoice::query()
            ->selectRaw('status, SUM(total_piastres) AS total, SUM(paid_piastres) AS paid')
            ->groupBy('status')
            ->get();

        $totals = ['outstanding' => 0, 'paid' => 0, 'overdue' => 0];
        foreach ($rows as $row) {
            $outstanding = max(0, (int) $row->total - (int) $row->paid);
            if ($row->status === 'paid') {
                $totals['paid'] += (int) $row->paid;
            } else {
                $totals['outstanding'] += $outstanding;
            }
        }

        $totals['overdue'] = (int) Invoice::query()
            ->whereDate('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->selectRaw('COALESCE(SUM(total_piastres - paid_piastres), 0) AS due')
            ->value('due');

        return $totals;
    }

    public function render(): View
    {
        return view('livewire.invoices.index');
    }
}
