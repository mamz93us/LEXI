<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Single-DB tenant.
 *
 * We extend stancl's BaseTenant for the tenancy resolution machinery, but
 * we deliberately omit TenantWithDatabase + HasDatabase — those are for
 * per-tenant databases, which we are not provisioning in this stage.
 *
 * stancl's GeneratesIds trait overrides Eloquent's `$incrementing` via a
 * `getIncrementing()` method that checks whether a UniqueIdentifierGenerator
 * is bound in the container. Since we disabled id_generator (slug ids are
 * supplied explicitly), we override `getIncrementing()` to always be false —
 * otherwise Eloquent would auto-increment string slug ids into 1, 2, 3.
 */
class Tenant extends BaseTenant
{
    use HasDomains;

    public $incrementing = false;

    protected $keyType = 'string';

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan',
            'settings',
            'branding',
        ];
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'branding' => 'array',
            'data' => 'array',
        ];
    }
}
