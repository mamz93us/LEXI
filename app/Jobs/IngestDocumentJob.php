<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InitialisesTenantFromRow;
use App\Models\ContractEmbedding;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Arabic\LegalChunker;
use App\Services\Documents\DocumentTextExtractor;
use App\Services\Embeddings\EmbeddingDriverManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * THE RAG ingestion pipeline (CLAUDE.md §6.1). For an uploaded document
 * version: extract text → ArabicNormalizer + LegalChunker (chunk on legal
 * structure, not fixed length) → embed each chunk → write tenant-scoped
 * `contract_embeddings` rows (vector inserted via raw SQL on Postgres).
 *
 * Without this job, `contract_embeddings` is never populated and RAG
 * retrieval returns nothing — the AI drafter has no firm-archive context.
 *
 * Quality gate (brief §6.1, "the #1 quality risk"): documents whose
 * extracted text looks like OCR garbage are marked `skipped`, not indexed,
 * so bad text doesn't poison retrieval.
 */
final class IngestDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InitialisesTenantFromRow;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Extraction (vision OCR) + embedding can be slow on a long contract. */
    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly int $documentVersionId) {}

    public function handle(): void
    {
        // Vision OCR of a multi-page PDF base64-encodes several MB, holds the
        // full Claude response, then chunks + embeds — comfortably past PHP's
        // default 128M on a lean worker. An OOM fatal bypasses the catch
        // block (it's not a Throwable), leaving the row stuck on `ingesting`.
        // Raise the ceiling for this job so failures surface as caught
        // exceptions with a reason instead of a silent SIGKILL.
        @ini_set('memory_limit', '512M');

        // Load the version WITHOUT eager-loading `document` — eager-loading
        // it here would apply Document's BelongsToTenant global scope while
        // no tenant is initialised yet (the Horizon worker starts tenant-
        // less), throwing "No active tenant context". Initialise tenancy
        // from the version's own tenant_id FIRST, then load the document.
        $version = DocumentVersion::withoutGlobalScopes()->find($this->documentVersionId);
        if (! $version || ! $version->storage_ref) {
            return;
        }

        $this->initialiseTenant($version->tenant_id);

        $document = Document::withoutGlobalScopes()->find($version->document_id);
        if (! $document) {
            return;
        }

        // Mark ingesting up-front so ANY subsequent failure (including a
        // service that can't be resolved) is recorded as `failed` with a
        // reason, never left stuck on `pending`.
        $document->update(['ingestion_status' => 'ingesting', 'ingestion_note' => null]);

        try {
            // Resolve heavy deps inside the try so a misconfigured driver /
            // missing key surfaces as a visible failure, not a silent crash.
            $extractor = app(DocumentTextExtractor::class);
            $chunker = app(LegalChunker::class);
            $embeddings = app(EmbeddingDriverManager::class);

            $text = $extractor->extract($version->storage_ref);

            if (trim($text) === '') {
                $document->update([
                    'ingestion_status' => 'skipped',
                    'ingestion_note' => 'لم يُستخرج نص من الملف.',
                    'embedding_count' => 0,
                ]);

                return;
            }

            if (! $this->passesQualityGate($text)) {
                $document->update([
                    'ingestion_status' => 'skipped',
                    'ingestion_note' => 'جودة النص المستخرج منخفضة (OCR رديء) — لم تتم الفهرسة لتجنّب إفساد الاسترجاع.',
                    'embedding_count' => 0,
                ]);

                return;
            }

            // Keep a copy of the extracted text on the document for search/audit.
            $document->update(['ocr_text' => Str::limit($text, 200000)]);

            $chunks = $chunker->chunk($text); // chunker normalises internally
            if (empty($chunks)) {
                $document->update(['ingestion_status' => 'skipped', 'ingestion_note' => 'لا توجد مقاطع قابلة للفهرسة.']);

                return;
            }

            // Re-ingest cleanly: drop any prior embeddings for this document.
            ContractEmbedding::where('document_id', $document->id)->delete();

            $driver = $embeddings->driver();
            $texts = array_map(fn (array $c) => $c['text'], $chunks);

            // Ingestion uses the default 'search_document' input type.
            $vectors = $driver->embedBatch($texts);
            $isPgvector = DB::getDriverName() === 'pgsql';
            $count = 0;

            foreach ($chunks as $i => $chunk) {
                $row = ContractEmbedding::create([
                    'document_id' => $document->id,
                    'source_version_id' => $version->id,
                    'chunk_index' => $i,
                    'chunk_text' => $chunk['text'],
                    'metadata' => [
                        'kind' => $chunk['kind'],
                        'heading' => $chunk['heading'],
                        'article_no' => $chunk['article_no'],
                        'contract_type' => $document->type,
                        'document_title' => $document->title,
                        'language' => 'ar',
                    ],
                ]);

                // The pgvector column isn't Eloquent-mass-assignable — set it
                // via raw SQL. On non-pgsql (dev/tests) there is no vector
                // column; the lexical fallback in RagRetrievalService covers it.
                if ($isPgvector && isset($vectors[$i]) && is_array($vectors[$i])) {
                    $literal = '['.implode(',', $vectors[$i]).']';
                    DB::statement(
                        'UPDATE contract_embeddings SET embedding = ?::vector WHERE id = ?',
                        [$literal, $row->id],
                    );
                }
                $count++;
            }

            $document->update([
                'ingestion_status' => 'ingested',
                'embedding_count' => $count,
                'ingestion_note' => null,
            ]);
        } catch (Throwable $e) {
            $document->update([
                'ingestion_status' => 'failed',
                'ingestion_note' => Str::limit($e->getMessage(), 300),
            ]);
            throw $e;
        }
    }

    /**
     * Reject obvious OCR garbage before indexing. Heuristics:
     *  - a minimum length
     *  - a reasonable ratio of Arabic letters + spaces (vs random symbols)
     * This is intentionally lenient — it only catches clearly-broken text.
     */
    private function passesQualityGate(string $text): bool
    {
        $len = mb_strlen($text);
        if ($len < 40) {
            return false;
        }

        // Count Arabic letters, digits, common punctuation, whitespace.
        preg_match_all('/[\p{Arabic}\p{N}\s\.\,\:\;\-\(\)«»"\']/u', $text, $m);
        $good = mb_strlen(implode('', $m[0] ?? []));
        $ratio = $len > 0 ? $good / $len : 0;

        // At least 60% of characters should be "expected" legal-text chars.
        return $ratio >= 0.6;
    }

    public function failed(Throwable $e): void
    {
        // `with('document')` eager-loads under withoutGlobalScopes, but to
        // be safe re-init the tenant so the Document scope doesn't throw.
        $version = DocumentVersion::withoutGlobalScopes()->find($this->documentVersionId);
        if (! $version) {
            return;
        }
        $this->initialiseTenant($version->tenant_id);

        $document = Document::withoutGlobalScopes()->find($version->document_id);
        if ($document && $document->ingestion_status !== 'failed') {
            $document->update([
                'ingestion_status' => 'failed',
                'ingestion_note' => Str::limit($e->getMessage(), 300),
            ]);
        }
    }
}
