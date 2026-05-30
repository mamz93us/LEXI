<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Jobs\IngestDocumentJob;
use App\Models\ContractEmbedding;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Detail extends Component
{
    use WithFileUploads;

    public Document $document;

    public $newVersionFile = null;

    public function mount(Document $document): void
    {
        $this->document = $document->load('versions.createdBy');
        // Audit the read of this privileged document (§7).
        $this->document->recordView();
    }

    public function uploadNewVersion(): void
    {
        $this->validate([
            'newVersionFile' => ['required', 'file', 'max:25600', 'mimes:pdf,doc,docx,png,jpg,jpeg,txt'],
        ]);

        $nextVersion = ($this->document->versions()->max('version_no') ?? 0) + 1;
        $ext = strtolower($this->newVersionFile->getClientOriginalExtension() ?: 'bin');
        $relativePath = sprintf(
            'tenants/%s/documents/%d/v%d.%s',
            $this->document->tenant_id,
            $this->document->id,
            $nextVersion,
            $ext,
        );
        Storage::put($relativePath, file_get_contents($this->newVersionFile->getRealPath()));

        $version = DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_no' => $nextVersion,
            'storage_ref' => $relativePath,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->document->update([
            'current_version_id' => $version->id,
            'format' => $ext,
            'ingestion_status' => 'pending',
        ]);

        // Re-ingest into the RAG store for the new version.
        IngestDocumentJob::dispatch($version->id);

        $this->reset('newVersionFile');
        $this->document->refresh()->load('versions.createdBy');
    }

    /**
     * The embedded chunks produced by the RAG ingestion pipeline. Shown
     * to the lawyer so they can verify the document was indexed sensibly
     * (right kind: preamble/article/signature, recognisable text).
     *
     * @return Collection<int, ContractEmbedding>
     */
    #[Computed]
    public function chunks(): Collection
    {
        return ContractEmbedding::query()
            ->where('document_id', $this->document->id)
            ->orderBy('chunk_index')
            ->get(['id', 'chunk_index', 'chunk_text', 'metadata']);
    }

    /** Re-queue ingestion for the current version. */
    public function reindex(): void
    {
        if (! $this->document->currentVersion) {
            return;
        }
        $this->document->update(['ingestion_status' => 'pending', 'ingestion_note' => null]);
        IngestDocumentJob::dispatch($this->document->currentVersion->id);
        unset($this->chunks);
        $this->document->refresh();
    }

    public function lockVersion(int $versionId): void
    {
        $v = DocumentVersion::query()->where('document_id', $this->document->id)->findOrFail($versionId);
        $v->update(['locked' => true]);
        $this->document->load('versions.createdBy');
    }

    public function render(): View
    {
        // Keep ingestion status fresh while wire:poll is active.
        if (in_array($this->document->ingestion_status, ['pending', 'ingesting'], true)) {
            $this->document->refresh();
        }

        return view('livewire.documents.detail');
    }
}
