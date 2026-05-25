<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiGeneration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'model',
        'prompt',
        'retrieved_chunk_ids',
        'output',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'retrieved_chunk_ids' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
