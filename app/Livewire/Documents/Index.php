<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Jobs\IngestDocumentJob;
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

    /**
     * Re-queue RAG ingestion for a document's current version. Useful when
     * an embeddings setting changed (e.g. switching driver from null →
     * Cohere, fixing the dimension) and the lawyer wants the document
     * re-chunked + re-embedded against the new configuration.
     */
    public function reindex(int $id): void
    {
        $doc = Document::query()->with('currentVersion')->findOrFail($id);
        if (! $doc->currentVersion) {
            session()->flash('error', 'لا يوجد إصدار للفهرسة.');

            return;
        }
        $doc->update(['ingestion_status' => 'pending', 'ingestion_note' => null]);
        IngestDocumentJob::dispatch($doc->currentVersion->id);
        session()->flash('saved', 'بدأت إعادة الفهرسة في الخلفية — حدّث الصفحة بعد قليل.');
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
