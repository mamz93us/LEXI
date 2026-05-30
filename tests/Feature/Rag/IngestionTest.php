<?php

declare(strict_types=1);

use App\Jobs\IngestDocumentJob;
use App\Models\ContractEmbedding;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Tenant;
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

    (new IngestDocumentJob($version->id))->handle();

    $document = $version->document->refresh();

    expect($document->ingestion_status)->toBe('ingested')
        ->and($document->embedding_count)->toBeGreaterThan(0)
        ->and(ContractEmbedding::where('document_id', $document->id)->count())
        ->toBe($document->embedding_count);
});

it('runs with NO active tenant (real Horizon worker scenario)', function () {
    // Regression test: the job must initialise tenancy from the row itself.
    // Eager-loading the document before that init tripped the tenant scope
    // and crashed the job at ~180ms with the document stuck on `pending`.
    Storage::fake(config('filesystems.default'));

    $body = 'المادة الأولى: نص قانوني كافٍ للفهرسة بطول مناسب لتجاوز بوابة الجودة.';
    $version = makeTxtDocument($body);
    $docId = $version->document_id;

    // Simulate the worker: drop all tenant context before running the job.
    tenancy()->end();
    expect(tenant())->toBeNull();

    (new IngestDocumentJob($version->id))->handle();

    $document = Document::withoutGlobalScopes()->find($docId);
    expect($document->ingestion_status)->toBe('ingested')
        ->and($document->embedding_count)->toBeGreaterThan(0);
});

it('skips garbage OCR text via the quality gate', function () {
    Storage::fake(config('filesystems.default'));

    // Mostly random latin/symbol noise → fails the Arabic-ratio gate.
    $version = makeTxtDocument(str_repeat('@#$%^&*<>{}[]|\\/~`xQzKjW ', 30));

    (new IngestDocumentJob($version->id))->handle();

    $document = $version->document->refresh();
    expect($document->ingestion_status)->toBe('skipped')
        ->and(ContractEmbedding::where('document_id', $document->id)->count())->toBe(0);
});

it('keeps embeddings tenant-scoped', function () {
    Storage::fake(config('filesystems.default'));

    $body = 'المادة الأولى: نص قانوني كافٍ للفهرسة في هذا الاختبار بطول مناسب.';
    $vA = makeTxtDocument($body);
    (new IngestDocumentJob($vA->id))->handle();

    expect(ContractEmbedding::count())->toBeGreaterThan(0); // firma sees its own

    tenancy()->end();
    tenancy()->initialize(Tenant::find('firmb'));
    expect(ContractEmbedding::count())->toBe(0); // firmb sees none of firma's
});
