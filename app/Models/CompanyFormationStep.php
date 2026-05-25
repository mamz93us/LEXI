<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFormationStep extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'step_order',
        'title',
        'authority',
        'status',
        'responsible_user_id',
        'fees_piastres',
        'expected_date',
        'actual_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_date' => 'date',
            'actual_date' => 'date',
            'fees_piastres' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
