<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TimeEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject_type',
        'subject_id',
        'worked_on',
        'minutes',
        'rate_piastres',
        'description',
        'billable',
        'invoiced',
    ];

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'minutes' => 'integer',
            'rate_piastres' => 'integer',
            'billable' => 'boolean',
            'invoiced' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function amountPiastres(): int
    {
        if (! $this->rate_piastres || ! $this->minutes) {
            return 0;
        }

        return intdiv($this->rate_piastres * $this->minutes, 60);
    }
}
