<?php

declare(strict_types=1);

namespace App\Livewire\Serials;

use App\Models\Serial;
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

    public function markCollected(int $id): void
    {
        Serial::query()->findOrFail($id)->update(['status' => 'collected']);
    }

    public function delete(int $id): void
    {
        Serial::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function serials(): Collection
    {
        return Serial::query()
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.serials.index');
    }
}
