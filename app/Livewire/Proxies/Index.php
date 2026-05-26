<?php

declare(strict_types=1);

namespace App\Livewire\Proxies;

use App\Models\Proxy;
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

    #[Url(as: 's')]
    public string $status = '';

    public function delete(int $id): void
    {
        Proxy::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function proxies(): Collection
    {
        return Proxy::query()
            ->with('client')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('notary_serial', 'like', "%{$this->search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%")
                            ->orWhere('name_ar', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('issue_date')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.proxies.index');
    }
}
