<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Serial extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'serial_no',
        'document_name',
        'issuing_authority',
        'owner_type',
        'owner_id',
        'fees_piastres',
        'issued_at',
        'status',
        'attachment_ref',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'fees_piastres' => 'integer',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
