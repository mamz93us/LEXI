<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'name_ar',
        'phone',
        'whatsapp_phone',
        'email',
        'national_id',
        'commercial_register_no',
        'address',
        'balance_piastres',
        'preferred_language',
        'is_blacklisted',
        'blacklist_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_blacklisted' => 'boolean',
            'balance_piastres' => 'integer',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }
}
