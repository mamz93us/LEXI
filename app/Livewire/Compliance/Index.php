<?php

declare(strict_types=1);

namespace App\Livewire\Compliance;

use App\Models\ComplianceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url(as: 's')]
    public string $status = '';

    public function markDone(int $id): void
    {
        $item = ComplianceItem::query()->findOrFail($id);
        $item->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function delete(int $id): void
    {
        ComplianceItem::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function items(): Collection
    {
        return ComplianceItem::query()
            ->with('company')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('due_date')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.compliance.index');
    }
}
