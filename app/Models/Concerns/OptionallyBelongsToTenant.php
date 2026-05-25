<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Scopes\OptionalTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For lookup tables (courts, case_types, …) that are central by default
 * but allow tenants to add their own rows. Differs from BelongsToTenant
 * by:
 *   - Permitting tenant_id IS NULL (shared seed rows)
 *   - Not throwing if tenancy is uninitialized — central commands like
 *     seeding need to insert shared rows.
 */
trait OptionallyBelongsToTenant
{
    public static function bootOptionallyBelongsToTenant(): void
    {
        static::addGlobalScope(new OptionalTenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id === null && tenancy()->initialized) {
                $model->tenant_id = tenant('id');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OptionalTenantScope::class);
    }

    public function isCentral(): bool
    {
        return $this->tenant_id === null;
    }
}
