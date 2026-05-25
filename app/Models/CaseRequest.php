<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'hearing_id',
        'case_id',
        'request_type_id',
        'requesting_party',
        'status',
        'notes',
    ];

    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }
}
