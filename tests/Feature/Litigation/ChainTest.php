<?php

declare(strict_types=1);

use App\Models\CaseType;
use App\Models\Client;
use App\Models\Court;
use App\Models\CourtType;
use App\Models\LegalCase;
use App\Models\Tenant;
use Database\Seeders\CaseTypeSeeder;
use Database\Seeders\CourtSeeder;
use Database\Seeders\CourtTypeSeeder;

beforeEach(function () {
    $this->seed(CourtTypeSeeder::class);
    $this->seed(CourtSeeder::class);
    $this->seed(CaseTypeSeeder::class);

    $this->tenant = Tenant::create(['id' => 'samir', 'name' => 'Samir']);
    tenancy()->initialize($this->tenant);

    $this->client = Client::create(['type' => 'individual', 'name' => 'X']);
});

afterEach(function () {
    tenancy()->end();
});

it('builds an ابتدائي → استئناف → نقض chain via parent_case_id', function () {
    $civil = CaseType::where('code', 'civil')->firstOrFail();
    $appealCourt = Court::query()->first();

    $firstInstance = LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'FI-2026-1',
        'title' => 'دعوى مدنية ابتدائية',
        'status' => 'open',
        'degree' => 'first_instance',
        'case_type_id' => $civil->id,
        'court_id' => $appealCourt?->id,
    ]);

    $appeal = LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'APP-2026-1',
        'title' => 'استئناف',
        'status' => 'open',
        'degree' => 'appeal',
        'case_type_id' => $civil->id,
        'parent_case_id' => $firstInstance->id,
        'appeal_type' => 'استئناف',
    ]);

    $cassation = LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'CASS-2026-1',
        'title' => 'نقض',
        'status' => 'open',
        'degree' => 'cassation',
        'case_type_id' => $civil->id,
        'parent_case_id' => $appeal->id,
        'appeal_type' => 'نقض',
    ]);

    $chain = $cassation->chain();

    expect($chain)->toHaveCount(3)
        ->and($chain->pluck('degree')->all())
        ->toBe(['first_instance', 'appeal', 'cassation']);
});

it('exposes seeded case types to every tenant', function () {
    expect(CaseType::where('code', 'civil')->exists())->toBeTrue();
    expect(CaseType::count())->toBeGreaterThanOrEqual(12);
});

it('isolates tenant-added court entries', function () {
    $appealTypeId = CourtType::where('code', 'appeal')->firstOrFail()->id;

    Court::create([
        'court_type_id' => $appealTypeId,
        'name_ar' => 'محكمة محلية لسمير فقط',
        'name_en' => 'Samir-only local court',
        'governorate' => 'القاهرة',
    ]);

    expect(Court::where('name_en', 'Samir-only local court')->exists())->toBeTrue();
    $samirVisibleCount = Court::count();

    tenancy()->end();

    Tenant::create(['id' => 'demo', 'name' => 'Demo']);
    tenancy()->initialize(Tenant::find('demo'));

    // Demo should see only the central courts — not Samir's custom court.
    expect(Court::where('name_en', 'Samir-only local court')->exists())->toBeFalse();
    expect(Court::count())->toBe($samirVisibleCount - 1);
});
