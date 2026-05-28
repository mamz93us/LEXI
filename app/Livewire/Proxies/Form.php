<?php

declare(strict_types=1);

namespace App\Livewire\Proxies;

use App\Jobs\ExtractProxyDataJob;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Proxy $proxy = null;

    public ?int $client_id = null;

    public string $type = 'specific';

    public ?string $notary_serial = null;

    public string $issue_date = '';

    public ?string $expiry_date = null;

    public ?string $scope = null;

    public string $status = 'valid';

    /** @var array<int, int> */
    public array $lawyer_ids = [];

    /** @var array<int, int> */
    public array $case_ids = [];

    /** New file upload (PDF or image) — extracted by Claude after save. */
    public ?TemporaryUploadedFile $upload = null;

    public function mount(?Proxy $proxy = null): void
    {
        if ($proxy && $proxy->exists) {
            $this->proxy = $proxy->load('authorizedLawyers', 'cases');
            $this->client_id = $proxy->client_id;
            $this->type = $proxy->type;
            $this->notary_serial = $proxy->notary_serial;
            $this->issue_date = $proxy->issue_date->toDateString();
            $this->expiry_date = $proxy->expiry_date?->toDateString();
            $this->scope = $proxy->scope;
            $this->status = $proxy->status;
            $this->lawyer_ids = $proxy->authorizedLawyers->pluck('id')->all();
            $this->case_ids = $proxy->cases->pluck('id')->all();
        } else {
            $this->issue_date = now()->toDateString();
        }
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->orderBy('name')->get();
    }

    #[Computed]
    public function lawyers()
    {
        return User::query()->orderBy('name')->get();
    }

    #[Computed]
    public function casesList()
    {
        return LegalCase::query()->orderByDesc('created_at')->limit(200)->get();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'type' => ['required', Rule::in(['general', 'specific'])],
            'notary_serial' => ['nullable', 'string', 'max:64'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['valid', 'expiring', 'expired', 'revoked'])],
            'lawyer_ids' => ['array'],
            'lawyer_ids.*' => ['integer', 'exists:users,id'],
            'case_ids' => ['array'],
            'case_ids.*' => ['integer', 'exists:cases,id'],
            // 50 MB max — scanned multi-page توكيلات from الشهر العقاري
            // can run 20-30+ MB at decent OCR quality.
            'upload' => ['nullable', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $payload = collect($data)->except(['lawyer_ids', 'case_ids', 'upload'])->all();

        if ($this->proxy) {
            $this->proxy->update($payload);
            $proxy = $this->proxy;
        } else {
            $proxy = Proxy::create($payload);
        }

        $proxy->authorizedLawyers()->sync($this->lawyer_ids);
        $proxy->cases()->sync($this->case_ids);

        if ($this->upload) {
            $this->storeUploadAndQueueExtraction($proxy);
        }

        return $this->redirectRoute('proxies.index', navigate: true);
    }

    /**
     * Copy the AI-extracted fields into the form (and the underlying row),
     * but only where the lawyer hasn't already entered a value. We never
     * overwrite manual edits — the lawyer always wins.
     */
    public function applyExtractedData(): void
    {
        if (! $this->proxy || ! is_array($this->proxy->extracted_data)) {
            return;
        }

        $proxyMeta = $this->proxy->extracted_data['proxy'] ?? [];

        // notary_serial, scope, dates, type → only if the form field is empty
        if (empty($this->notary_serial) && ! empty($proxyMeta['notary_serial'])) {
            $this->notary_serial = (string) $proxyMeta['notary_serial'];
        }
        if (empty($this->scope) && ! empty($proxyMeta['scope'])) {
            $this->scope = (string) $proxyMeta['scope'];
        }
        if (! empty($proxyMeta['issue_date'])) {
            // issue_date is required on the form — always overwrite if extracted
            // (so a freshly-uploaded scan replaces the default "today" placeholder).
            $this->issue_date = (string) $proxyMeta['issue_date'];
        }
        if (empty($this->expiry_date) && ! empty($proxyMeta['expiry_date'])) {
            $this->expiry_date = (string) $proxyMeta['expiry_date'];
        }
        if (in_array($proxyMeta['type'] ?? null, ['general', 'specific'], true)) {
            $this->type = $proxyMeta['type'];
        }

        // Persist the field changes immediately so the next page render shows
        // the updated values (without requiring the lawyer to click Save).
        $this->proxy->update([
            'notary_serial' => $this->notary_serial,
            'scope' => $this->scope,
            'issue_date' => $this->issue_date,
            'expiry_date' => $this->expiry_date ?: null,
            'type' => $this->type,
        ]);
    }

    private function storeUploadAndQueueExtraction(Proxy $proxy): void
    {
        // Per-tenant prefix so a future bucket-level audit stays clean.
        $tenantId = tenant('id') ?: 'unknown';
        $disk = config('filesystems.default');
        $path = $this->upload->store("proxies/{$tenantId}", $disk);

        $proxy->update([
            'file_path' => $path,
            'file_mime' => $this->upload->getMimeType(),
            'extraction_status' => 'pending',
            'extracted_text' => null,
            'extracted_data' => null,
        ]);

        ExtractProxyDataJob::dispatch($proxy->id);
    }

    public function render(): View
    {
        return view('livewire.proxies.form');
    }
}
