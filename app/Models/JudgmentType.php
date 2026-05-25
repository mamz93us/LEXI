<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\OptionallyBelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JudgmentType extends Model
{
    use OptionallyBelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name_ar',
        'name_en',
        'sort_order',
    ];
}
