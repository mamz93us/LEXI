<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Jobs\IngestDocumentJob;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\LegalCase;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Document $document = null;

    public string $title = '';

    public string $type = 'contract';

    public string $owner_kind = 'none'; // none|case|company|client

    public ?int $owner_id = null;

    public $file = null; // Livewire TemporaryUploadedFile

    public function mount(?Document $document = null): void
    {
        if ($document && $document->exists) {
            $this->document = $document;
            $this->title = $document->title;
            $this->type = $document->type;
            $this->owner_kind = match ($document->owner_type) {
                LegalCase::class => 'case',
                Company::class => 'company',
                Client::class => 'client',
                default => 'none',
            };
            $this->owner_id = $document->owner_id;
        }
    }

    #[Computed]
    public function ownerOptions(): array
    {
        return match ($this->owner_kind) {
            'case' => LegalCase::query()->orderByDesc('created_at')->limit(200)->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->case_number.' — '.$c->title])->all(),
            'company' => Company::query()->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name_ar ?? $c->name])->all(),
            'client' => Client::query()->orderBy('name')->get()
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name_ar ?? $c->name])->all(),
            default => [],
        };
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['contract', 'poa', 'memo', 'filing', 'other'])],
            'owner_kind' => ['required', Rule::in(['none', 'case', 'company', 'client'])],
            'owner_id' => ['nullable', 'integer'],
            // Max 25MB. Accept PDFs, Word, images, plain text.
            'file' => [
                $this->document ? 'nullable' : 'required',
                'file',
                'max:25600',
                'mimes:pdf,doc,docx,png,jpg,jpeg,txt',
            ],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $ownerType = match ($data['owner_kind']) {
            'case' => LegalCase::class,
            'company' => Company::class,
            'client' => Client::class,
            default => null,
        };

        if ($this->document) {
            $this->document->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerType ? $data['owner_id'] : null,
            ]);
            $document = $this->document;
        } else {
            $document = Document::create([
                'title' => $data['title'],
                'type' => $data['type'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerType ? $data['owner_id'] : null,
                'format' => 'docx',
            ]);
        }

        // If a new file was uploaded, store it and create a new version.
        if ($this->file) {
            $nextVersion = ($document->versions()->max('version_no') ?? 0) + 1;
            $ext = strtolower($this->file->getClientOriginalExtension() ?: 'bin');
            $relativePath = sprintf(
                'tenants/%s/documents/%d/v%d.%s',
                $document->tenant_id,
                $document->id,
                $nextVersion,
                $ext,
            );

            Storage::put($relativePath, file_get_contents($this->file->getRealPath()));

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_no' => $nextVersion,
                'storage_ref' => $relativePath,
                'created_by_user_id' => auth()->id(),
            ]);

            $document->update([
                'current_version_id' => $version->id,
                'format' => $ext,
                'ingestion_status' => 'pending',
            ]);

            // Kick off RAG ingestion: extract → normalise → chunk → embed →
            // contract_embeddings. Runs in the background (vision OCR +
            // embedding can take a while). The document Detail page surfaces
            // the status.
            IngestDocumentJob::dispatch($version->id);
        }

        return $this->redirectRoute('documents.show', $document, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.documents.form');
    }
}
