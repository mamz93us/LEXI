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
        'nationality',
        'religion',
        'profession',
        'date_of_birth',
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
            'date_of_birth' => 'date',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    /**
     * Map this client to the predefined variable-catalog fields a contract
     * party expects (used by Wizard → VariableResolver → token substitution).
     *
     * Keys here MUST match VariableCatalog::PARTY_FIELDS so the catalog can
     * build dotted tokens like `seller.national_id`, `buyer.address`, etc.
     *
     * @return array<string, string|null>
     */
    public function toAiVariables(): array
    {
        return [
            'name' => $this->name_ar ?: $this->name,
            'name_en' => $this->name,
            'national_id' => $this->national_id,
            'commercial_register_no' => $this->commercial_register_no,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp_phone,
            'email' => $this->email,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'profession' => $this->profession,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'type' => $this->type,
        ];
    }
}
