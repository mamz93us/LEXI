<?php

declare(strict_types=1);

use App\Jobs\IngestDocumentJob;
use App\Models\ContractEmbedding;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Tenant;
use App\Services\Arabic\LegalChunker;
use App\Services\Documents\DocumentTextExtractor;
use App\Services\Embeddings\EmbeddingDriverManager;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Null embedding driver → deterministic dummy vectors, no API calls.
    config(['lexa.embeddings.driver' => 'null']);
    Tenant::create(['id' => 'firma', 'name' => 'Firm A']);
    Tenant::create(['id' => 'firmb', 'name' => 'Firm B']);
    tenancy()->initialize(Tenant::find('firma'));
});

afterEach(function () {
    tenancy()->end();
});

function makeTxtDocument(string $body): DocumentVersion
{
    $disk = Storage::disk(config('filesystems.default'));
    $document = Document::create(['title' => 'عقد بيع', 'type' => 'contract', 'format' => 'txt']);
    $path = "tenants/{$document->tenant_id}/documents/{$document->id}/v1.txt";
    $disk->put($path, $body);

    $version = DocumentVersion::create([
        'document_id' => $document->id,
        'version_no' => 1,
        'storage_ref' => $path,
    ]);
    $document->update(['current_version_id' => $version->id, 'ingestion_status' => 'pending']);

    return $version;
}

it('chunks and embeds a txt document into contract_embeddings', function () {
    Storage::fake(config('filesystems.default'));

    $body = "ديباجة هذا العقد المبرم بين الطرفين.\n".
        "المادة الأولى: يلتزم البائع بتسليم المبيع.\n".
        "المادة الثانية: يلتزم المشتري بسداد الثمن.\n".
        'التوقيع: الطرف الأول والطرف الثاني.';
    $version = makeTxtDocument($body);

    (new IngestDocumentJob($version->id))->handle(
        app(DocumentTextExtractor::class),
        app(LegalChunker::class),
        app(EmbeddingDriverManager::class),
    );

    $document = $version->document->refresh();

    expect($document->ingestion_status)->toBe('ingested')
        ->and($document->embedding_count)->toBeGreaterThan(0)
        ->and(ContractEmbedding::where('document_id', $document->id)->count())
        ->toBe($document->embedding_count);
});

it('skips garbage OCR text via the quality gate', function () {
    Storage::fake(config('filesystems.default'));

    // Mostly random latin/symbol noise → fails the Arabic-ratio gate.
    $version = makeTxtDocument(str_repeat('@#$%^&*<>{}[]|\\/~`xQzKjW ', 30));

    (new IngestDocumentJob($version->id))->handle(
        app(DocumentTextExtractor::class),
        app(LegalChunker::class),
        app(EmbeddingDriverManager::class),
    );

    $document = $version->document->refresh();
    expect($document->ingestion_status)->toBe('skipped')
        ->and(ContractEmbedding::where('document_id', $document->id)->count())->toBe(0);
});

it('keeps embeddings tenant-scoped', function () {
    Storage::fake(config('filesystems.default'));

    $body = 'المادة الأولى: نص قانوني كافٍ للفهرسة في هذا الاختبار بطول مناسب.';
    $vA = makeTxtDocument($body);
    (new IngestDocumentJob($vA->id))->handle(
        app(DocumentTextExtractor::class),
        app(LegalChunker::class),
        app(EmbeddingDriverManager::class),
    );

    expect(ContractEmbedding::count())->toBeGreaterThan(0); // firma sees its own

    tenancy()->end();
    tenancy()->initialize(Tenant::find('firmb'));
    expect(ContractEmbedding::count())->toBe(0); // firmb sees none of firma's
});
