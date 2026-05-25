<?php

declare(strict_types=1);

use App\Models\CaseRequest;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\RequestType;
use App\Models\Tenant;
use Database\Seeders\CaseTypeSeeder;
use Database\Seeders\RequestTypeSeeder;

beforeEach(function () {
    $this->seed(CaseTypeSeeder::class);
    $this->seed(RequestTypeSeeder::class);

    $this->tenant = Tenant::create(['id' => 'samir', 'name' => 'Samir']);
    tenancy()->initialize($this->tenant);

    $civil = CaseType::where('code', 'civil')->firstOrFail();
    $this->client = Client::create(['type' => 'individual', 'name' => 'X']);
    $this->case = LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'C-1',
        'title' => 'قضية',
        'status' => 'open',
        'degree' => 'first_instance',
        'case_type_id' => $civil->id,
    ]);
});

afterEach(function () {
    tenancy()->end();
});

it('attaches multiple structured requests to a single hearing', function () {
    $hearing = Hearing::create([
        'case_id' => $this->case->id,
        'session_date' => '2026-06-15',
        'purpose' => 'مرافعة',
        'next_date' => '2026-07-01',
    ]);

    $codes = ['postponement', 'submit_memo', 'expert_request'];
    foreach ($codes as $code) {
        $type = RequestType::where('code', $code)->firstOrFail();
        CaseRequest::create([
            'hearing_id' => $hearing->id,
            'case_id' => $this->case->id,
            'request_type_id' => $type->id,
            'requesting_party' => 'claimant',
            'status' => 'pending',
        ]);
    }

    $reloaded = Hearing::with('requests.requestType')->find($hearing->id);

    expect($reloaded->requests)->toHaveCount(3)
        ->and($reloaded->requests->pluck('requestType.code')->all())
        ->toMatchArray($codes);
});
