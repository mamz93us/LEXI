<?php

declare(strict_types=1);

namespace App\Livewire\Clauses;

use App\Models\Clause;
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

    #[Url(as: 't')]
    public string $topic = '';

    public function delete(int $id): void
    {
        Clause::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function clauses(): Collection
    {
        return Clause::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->topic !== '', fn ($q) => $q->where('topic', $this->topic))
            ->with('currentVersion')
            ->orderBy('topic')
            ->orderBy('title')
            ->limit(300)
            ->get();
    }

    #[Computed]
    public function topics(): Collection
    {
        return Clause::query()->select('topic')->distinct()->pluck('topic');
    }

    public function render(): View
    {
        return view('livewire.clauses.index');
    }
}
