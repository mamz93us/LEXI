<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\OptionallyBelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use OptionallyBelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'court_type_id',
        'parent_id',
        'name_ar',
        'name_en',
        'governorate',
        'sort_order',
    ];

    public function courtType(): BelongsTo
    {
        return $this->belongsTo(CourtType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
