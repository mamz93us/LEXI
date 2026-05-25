<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * "Case" is a reserved word in PHP so the model is named LegalCase.
 * The underlying table is still `cases`.
 */
class LegalCase extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'case_number',
        'title',
        'status',
        'dispute_value_piastres',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispute_value_piastres' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
