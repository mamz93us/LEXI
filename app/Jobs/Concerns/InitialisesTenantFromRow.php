<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\Tenant;

/**
 * Re-establish the correct tenant context for a queued job from a row's
 * `tenant_id`, regardless of what tenant (if any) a warm Horizon worker
 * already has initialised.
 *
 * Why this is a security fix, not a convenience:
 * We do NOT enable stancl's QueueTenancyBootstrapper, so tenancy is not
 * automatically serialised into jobs nor reset between them. A long-lived
 * worker that just finished a job for Firm A keeps Firm A initialised. The
 * old guard `if (! tenant()) { initialize() }` would then SKIP re-init and
 * run Firm B's job under Firm A — reading A's API key, writing under A's
 * scope. This helper instead forces the worker onto the row's own tenant
 * every time, ending any stale context first.
 */
trait InitialisesTenantFromRow
{
    protected function initialiseTenant(?string $tenantId): void
    {
        if (! $tenantId) {
            return;
        }

        // Already on the right tenant — nothing to do.
        if (tenant() && (string) tenant('id') === (string) $tenantId) {
            return;
        }

        // Drop whatever tenant a previous job left initialised.
        if (tenant()) {
            tenancy()->end();
        }

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            tenancy()->initialize($tenant);
        }
    }
}
