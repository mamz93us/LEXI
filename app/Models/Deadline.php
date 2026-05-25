<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Deadline extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'deadline_for_type',
        'deadline_for_id',
        'type',
        'due_date',
        'alert_offsets_days',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'alert_offsets_days' => 'array',
        ];
    }

    public function deadlineFor(): MorphTo
    {
        return $this->morphTo();
    }

    public function daysUntilDue(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date, false);
    }
}
