<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAsset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'client_id',
        'asset_type',
        'title',
        'classification',
        'office_serial',
        'filed_on',
        'granted_on',
        'renewal_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'filed_on' => 'date',
            'granted_on' => 'date',
            'renewal_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
