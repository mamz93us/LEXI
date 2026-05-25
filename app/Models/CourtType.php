<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\OptionallyBelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtType extends Model
{
    use OptionallyBelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name_ar',
        'name_en',
        'sort_order',
    ];

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }
}
