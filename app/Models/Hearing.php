<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hearing extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'case_id',
        'court_id',
        'session_date',
        'purpose',
        'attended_by',
        'outcome',
        'postponement_reason',
        'next_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'next_date' => 'date',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CaseRequest::class);
    }
}
