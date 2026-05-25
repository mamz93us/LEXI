<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Filter every query to rows owned by the currently active tenant.
     *
     * If no tenant is active we throw — silent empty results would mask
     * the real bug (forgetting to initialize tenancy) and could leak data
     * if an "empty" assertion is interpreted as "no rows."
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! tenancy()->initialized) {
            throw new \RuntimeException(sprintf(
                'No active tenant context when querying [%s]. Initialize '.
                'tenancy first, or use withoutTenantScope() for explicit '.
                'cross-tenant operations.',
                $model::class
            ));
        }

        $builder->where($model->qualifyColumn('tenant_id'), tenant('id'));
    }
}
