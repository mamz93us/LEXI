<?php

declare(strict_types=1);

namespace App\Livewire\Templates;

use App\Models\Template;
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
        Template::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function templates(): Collection
    {
        return Template::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->with('currentVersion')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.templates.index');
    }
}
