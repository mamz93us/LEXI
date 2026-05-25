<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\ComplianceItem;
use App\Models\Deadline;
use App\Models\Hearing;
use App\Models\Invoice;
use App\Models\LegalCase;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Computed]
    public function stats(): array
    {
        $today = CarbonImmutable::today();
        $weekFromNow = $today->addDays(7);

        return [
            'active_cases' => LegalCase::query()->where('status', 'open')->count(),
            'hearings_next_7_days' => Hearing::query()
                ->whereBetween('session_date', [$today, $weekFromNow])
                ->count(),
            'open_deadlines' => Deadline::query()
                ->where('status', 'open')
                ->whereBetween('due_date', [$today, $weekFromNow])
                ->count(),
            'overdue_compliance' => ComplianceItem::query()
                ->where('status', 'open')
                ->whereDate('due_date', '<', $today)
                ->count(),
            'outstanding_invoices_egp' => intdiv(
                (int) Invoice::query()
                    ->where('status', '!=', 'paid')
                    ->where('status', '!=', 'void')
                    ->selectRaw('COALESCE(SUM(total_piastres - paid_piastres), 0) AS due')
                    ->value('due'),
                100,
            ),
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
