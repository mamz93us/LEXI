<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\ScheduleDeadlineReminders;
use App\Models\Deadline;
use App\Models\Judgment;
use App\Services\Litigation\DeadlineCalculator;
use Illuminate\Support\Facades\Bus;

class JudgmentObserver
{
    public function __construct(private readonly DeadlineCalculator $calculator) {}

    public function created(Judgment $judgment): void
    {
        $result = $this->calculator->compute($judgment);
        if (! $result) {
            return;
        }

        $judgment->forceFill(['appeal_deadline' => $result['date']->toDateString()])->save();

        $deadline = Deadline::create([
            'tenant_id' => $judgment->tenant_id,
            'deadline_for_type' => $judgment->getMorphClass(),
            'deadline_for_id' => $judgment->id,
            'type' => $result['type'],
            'due_date' => $result['date']->toDateString(),
            'alert_offsets_days' => config('lexa_deadlines.reminder_offsets_days'),
            'status' => 'open',
        ]);

        Bus::dispatch(new ScheduleDeadlineReminders($deadline->id));
    }
}
