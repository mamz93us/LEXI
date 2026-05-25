<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Central / shared reference data (tenant_id NULL).
            CourtTypeSeeder::class,
            CourtSeeder::class,
            CaseTypeSeeder::class,
            RequestTypeSeeder::class,
            JudgmentTypeSeeder::class,

            // Demo tenants (only run locally — gate before deploying to prod).
            DemoTenantsSeeder::class,
        ]);
    }
}
