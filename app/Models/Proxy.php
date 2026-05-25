<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proxy extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'proxies';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'type',
        'notary_serial',
        'issue_date',
        'expiry_date',
        'scope',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function authorizedLawyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proxy_user');
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(LegalCase::class, 'proxy_case', 'proxy_id', 'case_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
