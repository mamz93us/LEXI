<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
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
        ]);

        $this->reset('newVersionFile');
        $this->document->refresh()->load('versions.createdBy');
    }

    public function lockVersion(int $versionId): void
    {
        $v = DocumentVersion::query()->where('document_id', $this->document->id)->findOrFail($versionId);
        $v->update(['locked' => true]);
        $this->document->load('versions.createdBy');
    }

    public function render(): View
    {
        return view('livewire.documents.detail');
    }
}
