<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClauseVersion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'clause_id',
        'version_no',
        'body',
        'condition_expression',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'condition_expression' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function clause(): BelongsTo
    {
        return $this->belongsTo(Clause::class);
    }
}
