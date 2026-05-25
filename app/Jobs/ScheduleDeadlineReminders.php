<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Deadline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Expands a deadline's `alert_offsets_days` into individual reminder
 * dispatches (e.g. SendDeadlineReminder). Phase 1 stub — just logs.
 * Wire actual notifications in Phase 2/3.
 */
class ScheduleDeadlineReminders implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $deadlineId) {}

    public function handle(): void
    {
        $deadline = Deadline::query()->withoutTenantScope()->find($this->deadlineId);
        if (! $deadline) {
            return;
        }

        $offsets = $deadline->alert_offsets_days ?? [];
        foreach ($offsets as $offset) {
            $fireAt = $deadline->due_date->copy()->addDays((int) $offset);
            Log::info('lexa.deadline.reminder_scheduled', [
                'deadline_id' => $deadline->id,
                'fire_at' => $fireAt->toDateString(),
                'offset_days' => $offset,
            ]);
        }
    }
}
