<?php

declare(strict_types=1);

use App\Models\Tenant;
use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\Domain;

return [
    // --- Models -----------------------------------------------------------
    'tenant_model' => Tenant::class,
    'domain_model' => Domain::class,
    // Tenant ids are slugs we set explicitly (e.g. "samir"), not auto-generated.
    'id_generator' => null,

    // --- Central (landlord) domains --------------------------------------
    // Any request hitting these domains is treated as the bare landlord —
    // no tenant initialised. In production we ship as single-firm with
    // the app domain itself being a TENANT domain (mapped via a Domain
    // row), so CENTRAL_DOMAIN is left empty there.
    //
    // Dev keeps `lexa.test` central so contributors get the existing
    // samir/demo subdomain demo flow.
    'central_domains' => array_values(array_filter([
        env('CENTRAL_DOMAIN', 'lexa.test'),
        '127.0.0.1',
        'localhost',
    ])),

    // --- Bootstrappers ---------------------------------------------------
    // Single-DB mode: no DatabaseTenancyBootstrapper. We use our own
    // BelongsToTenant trait + global scope to filter queries by tenant_id.
    //
    // NOT enabling FilesystemTenancyBootstrapper: it rewrites
    // `storage_path()` to inject `tenant<id>/` after `/storage/`, which
    // breaks Laravel's real-time facade cache (it tries to write to
    // `storage/tenantXYZ/framework/cache/` — a directory that doesn't
    // exist) and breaks file uploads end-to-end. Our isolation is per-row
    // (tenant_id + BelongsToTenant global scope) plus manual per-tenant
    // prefixing in upload code (e.g. `proxies/{tenant_id}/...`). When we
    // get to S3 in Phase 2, we'll prefix at the disk level explicitly
    // rather than via the bootstrapper.
    'bootstrappers' => [
        CacheTenancyBootstrapper::class,
    ],

    // --- Cache tagging ---------------------------------------------------
    'cache' => [
        'tag_base' => 'tenant',
    ],

    // --- Filesystem scoping (Phase 2 will lean on this for S3 prefixes) --
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        // `asset()` MUST keep public URLs unprefixed (e.g. /build/...) so
        // Vite-built CSS/JS resolves correctly. Use `tenant_asset()`
        // explicitly for tenant-private uploads — not the generic helper.
        'asset_helper_tenancy' => false,
    ],

    // --- Redis prefixing -------------------------------------------------
    // We use redis only for cache/session/queue at the app level — these
    // don't need tenant prefixing because cache tagging above handles it
    // and sessions are per-user (and the user belongs to a tenant).
    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            // 'default',
        ],
    ],

    // --- Features --------------------------------------------------------
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
    ],

    'routes' => true,

    // Single-DB mode: no per-tenant migration directory.
    'migration_parameters' => [
        '--force' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
