<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Judgment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'case_id',
        'judgment_type_id',
        'judgment_date',
        'presence_type',
        'summary',
        'appeal_deadline',
    ];

    protected function casts(): array
    {
        return [
            'judgment_date' => 'date',
            'appeal_deadline' => 'date',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function judgmentType(): BelongsTo
    {
        return $this->belongsTo(JudgmentType::class);
    }

    public function deadlines(): MorphMany
    {
        return $this->morphMany(Deadline::class, 'deadline_for');
    }
}
