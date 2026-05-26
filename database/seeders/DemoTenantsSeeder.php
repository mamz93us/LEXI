<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantsSeeder extends Seeder
{
    public function run(): void
    {
        // Hard gate: never seed throwaway tenants on a production deploy.
        // The deploy script only calls the reference-data seeders by name,
        // but a tired admin running `php artisan db:seed` should still be
        // protected.
        if (app()->environment('production')) {
            $this->command?->warn('DemoTenantsSeeder skipped: APP_ENV=production');

            return;
        }

        $this->createTenant(
            id: 'samir',
            name: 'Samir Group Legal',
            domain: 'samir.'.config('tenancy.central_domains.0', 'lexa.test'),
            partnerEmail: 'partner@samir.test',
            partnerName: 'محمد سمير',
        );

        $this->createTenant(
            id: 'demo',
            name: 'Demo Firm',
            domain: 'demo.'.config('tenancy.central_domains.0', 'lexa.test'),
            partnerEmail: 'partner@demo.test',
            partnerName: 'مكتب التجريب',
        );
    }

    private function createTenant(
        string $id,
        string $name,
        string $domain,
        string $partnerEmail,
        string $partnerName,
    ): void {
        /** @var Tenant $tenant */
        $tenant = Tenant::create([
            'id' => $id,
            'name' => $name,
            'plan' => 'free',
            'settings' => [],
            'branding' => [],
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        // Partner is created on the *central* DB (single-DB mode) with
        // tenant_id pointing at this firm. Bypass any tenant context
        // for this insert — there's no active tenancy during seeding.
        User::query()->create([
            'tenant_id' => $id,
            'name' => $partnerName,
            'email' => $partnerEmail,
            'password' => Hash::make('lexa-dev'),
            'role' => UserRole::Partner->value,
            'locale' => 'ar',
            'email_verified_at' => now(),
        ]);
    }
}
