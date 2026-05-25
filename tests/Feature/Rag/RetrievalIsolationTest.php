<?php

declare(strict_types=1);

use App\Models\ContractEmbedding;
use App\Models\Tenant;
use App\Services\Rag\RagRetrievalService;

beforeEach(function () {
    $this->firma = Tenant::create(['id' => 'firma', 'name' => 'Firm A']);
    $this->firmb = Tenant::create(['id' => 'firmb', 'name' => 'Firm B']);
});

afterEach(function () {
    tenancy()->end();
});

it('never returns another tenant\'s chunks via the retrieval SQL', function () {
    // Seed each tenant with a distinctive chunk.
    tenancy()->initialize($this->firma);
    ContractEmbedding::create([
        'chunk_index' => 0,
        'chunk_text' => 'بنود السرية الخاصة بالشركة الاولي ABC',
        'metadata' => ['contract_type' => 'nda'],
    ]);
    tenancy()->end();

    tenancy()->initialize($this->firmb);
    ContractEmbedding::create([
        'chunk_index' => 0,
        'chunk_text' => 'بنود السرية الخاصة بالشركة الثانية XYZ',
        'metadata' => ['contract_type' => 'nda'],
    ]);

    /** @var RagRetrievalService $svc */
    $svc = app(RagRetrievalService::class);

    // We are still in Firm B's context — searching for ABC must NOT
    // return Firm A's chunk even though the term matches lexically.
    $results = $svc->retrieve('السرية ABC');

    expect($results->pluck('chunk_text')->all())
        ->each->not->toContain('ABC')
        ->and($results->every(fn ($r) => str_contains($r['chunk_text'], 'XYZ')))->toBeTrue();
});

it('throws when no tenant is active', function () {
    /** @var RagRetrievalService $svc */
    $svc = app(RagRetrievalService::class);

    expect(fn () => $svc->retrieve('test'))
        ->toThrow(RuntimeException::class, 'without an active tenant');
});
