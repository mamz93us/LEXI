<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
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
    public string $type = '';

    public function delete(int $id): void
    {
        $doc = Document::query()->findOrFail($id);
        $doc->delete();
    }

    #[Computed]
    public function documents(): Collection
    {
        return Document::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->with('currentVersion.createdBy')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.documents.index');
    }
}
