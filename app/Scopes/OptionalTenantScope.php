<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope for reference (lookup) tables that are partly central and partly
 * tenant-owned. Returns rows where tenant_id IS NULL (the seeded central
 * data) OR tenant_id = the current tenant (additions by that firm).
 *
 * When no tenant is active, only the shared (NULL) rows are returned.
 */
class OptionalTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $column = $model->qualifyColumn('tenant_id');

        if (tenancy()->initialized) {
            $tenantId = tenant('id');
            $builder->where(function (Builder $q) use ($column, $tenantId) {
                $q->whereNull($column)->orWhere($column, $tenantId);
            });

            return;
        }

        $builder->whereNull($column);
    }
}
