<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
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

    public function delete(int $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('delete', $client);
        $client->delete();
    }

    #[Computed]
    public function clients(): Collection
    {
        return Client::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name_ar', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Client::class);

        return view('livewire.clients.index');
    }
}
