<?php

declare(strict_types=1);

use App\Jobs\ScheduleDeadlineReminders;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Deadline;
use App\Models\Judgment;
use App\Models\JudgmentType;
use App\Models\LegalCase;
use App\Models\Tenant;
use Database\Seeders\CaseTypeSeeder;
use Database\Seeders\JudgmentTypeSeeder;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->seed(CaseTypeSeeder::class);
    $this->seed(JudgmentTypeSeeder::class);

    $this->tenant = Tenant::create(['id' => 'samir', 'name' => 'Samir']);
    tenancy()->initialize($this->tenant);

    $civil = CaseType::where('code', 'civil')->firstOrFail();
    $this->client = Client::create(['type' => 'individual', 'name' => 'X']);
    $this->case = LegalCase::create([
        'client_id' => $this->client->id,
        'case_number' => 'C-DL',
        'title' => 'قضية',
        'status' => 'open',
        'degree' => 'first_instance',
        'case_type_id' => $civil->id,
    ]);
});

afterEach(function () {
    tenancy()->end();
});

it('auto-creates an appeal deadline when a first-instance judgment is entered', function () {
    Bus::fake();

    $final = JudgmentType::where('code', 'final')->firstOrFail();
    $judgment = Judgment::create([
        'case_id' => $this->case->id,
        'judgment_type_id' => $final->id,
        'judgment_date' => '2026-06-01',
        'presence_type' => 'in_presence',
    ]);

    // 40 days is the placeholder civil first_instance_to_appeal window.
    $expectedDeadline = '2026-07-11';

    $deadline = Deadline::query()
        ->where('deadline_for_id', $judgment->id)
        ->where('deadline_for_type', $judgment->getMorphClass())
        ->first();

    expect($deadline)->not->toBeNull()
        ->and($deadline->due_date->toDateString())->toBe($expectedDeadline)
        ->and($deadline->type)->toBe('first_instance_to_appeal')
        ->and($judgment->fresh()->appeal_deadline->toDateString())->toBe($expectedDeadline);

    Bus::assertDispatched(ScheduleDeadlineReminders::class);
});

it('does not create a deadline for a cassation judgment (no further challenge)', function () {
    $this->case->update(['degree' => 'cassation']);
    $final = JudgmentType::where('code', 'final')->firstOrFail();

    Judgment::create([
        'case_id' => $this->case->id,
        'judgment_type_id' => $final->id,
        'judgment_date' => '2026-06-01',
        'presence_type' => 'in_presence',
    ]);

    expect(Deadline::count())->toBe(0);
});

it('uses a different window for criminal_misdemeanor cases', function () {
    $criminal = CaseType::where('code', 'criminal_misdemeanor')->firstOrFail();
    $this->case->update(['case_type_id' => $criminal->id]);

    $final = JudgmentType::where('code', 'final')->firstOrFail();
    $judgment = Judgment::create([
        'case_id' => $this->case->id,
        'judgment_type_id' => $final->id,
        'judgment_date' => '2026-06-01',
        'presence_type' => 'in_presence',
    ]);

    // 10-day misdemeanour appeal window per the placeholder config.
    expect($judgment->fresh()->appeal_deadline->toDateString())->toBe('2026-06-11');
});
