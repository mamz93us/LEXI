<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InitialisesTenantFromRow;
use App\Models\Proxy;
use App\Services\Proxies\ProxyDataExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Send a freshly-uploaded proxy file to Claude for structured-data
 * extraction in the background. The Form mutates `extraction_status`
 * to `pending` and dispatches this job; the job flips to `extracting`
 * while it works, then `extracted` (success) or `failed`.
 *
 * Lawyer can then review the parsed fields on the proxy detail page
 * and accept/edit them.
 *
 * Tenancy: like RunAiGenerationJob we re-initialize the originating
 * tenant by id so SettingsService finds the right API key.
 */
final class ExtractProxyDataJob implements ShouldQueue
{
    use Dispatchable;
    use InitialisesTenantFromRow;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Vision extraction is single-turn and small — give it 5 minutes. */
    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public readonly int $proxyId) {}

    public function handle(ProxyDataExtractor $extractor): void
    {
        $proxy = Proxy::withoutGlobalScopes()->find($this->proxyId);
        if (! $proxy || ! $proxy->file_path) {
            return;
        }

        // Force the correct tenant even if a warm worker still has a
        // different one initialised from the previous job.
        $this->initialiseTenant($proxy->tenant_id);

        $proxy->update(['extraction_status' => 'extracting']);

        try {
            $result = $extractor->extract($proxy);
            $proxy->update([
                'extracted_text' => $result['extracted_text'],
                'extracted_data' => $result['extracted_data'],
                'extraction_status' => 'extracted',
            ]);
            $this->backfillProxyFromExtraction($proxy);
        } catch (Throwable $e) {
            $proxy->update([
                'extraction_status' => 'failed',
                'extracted_text' => '[extraction failed: '.$e->getMessage().']',
            ]);
            throw $e;
        }
    }

    /**
     * If the lawyer left the canonical proxy fields blank (notary_serial,
     * scope, etc.), copy them in from the AI extraction so the proxy
     * detail page shows usable values immediately.
     */
    private function backfillProxyFromExtraction(Proxy $proxy): void
    {
        $data = $proxy->extracted_data['proxy'] ?? [];
        $updates = [];

        foreach (['notary_serial', 'scope'] as $field) {
            if (empty($proxy->{$field}) && ! empty($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }
        if ($proxy->type === 'specific' && in_array($data['type'] ?? null, ['general', 'specific'], true)) {
            // Don't overwrite the lawyer's manually-set type unless it was the default.
            // The form defaults to 'specific'; treat that as "unspecified" only if no extraction has happened yet.
        }
        if (empty($updates)) {
            return;
        }
        $proxy->update($updates);
    }

    public function failed(Throwable $e): void
    {
        $proxy = Proxy::withoutGlobalScopes()->find($this->proxyId);
        if ($proxy && $proxy->extraction_status !== 'failed') {
            $proxy->update([
                'extraction_status' => 'failed',
                'extracted_text' => '[extraction failed: '.$e->getMessage().']',
            ]);
        }
    }
}
