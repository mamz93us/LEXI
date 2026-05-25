<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'name',
        'name_ar',
        'legal_form',
        'commercial_register_no',
        'tax_card_no',
        'gafi_file_no',
        'capital_piastres',
        'activity_codes',
        'status',
        'formation_stage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'activity_codes' => 'array',
            'capital_piastres' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function formationSteps(): HasMany
    {
        return $this->hasMany(CompanyFormationStep::class)->orderBy('step_order');
    }

    public function shareholders(): HasMany
    {
        return $this->hasMany(Shareholder::class);
    }

    public function complianceItems(): HasMany
    {
        return $this->hasMany(ComplianceItem::class);
    }

    public function ipAssets(): HasMany
    {
        return $this->hasMany(IpAsset::class);
    }
}
