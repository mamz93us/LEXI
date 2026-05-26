<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Create (or update) the single firm that lives at the main APP_URL.
 *
 * Idempotent: re-running with the same slug updates name + domain
 * mapping without touching the existing partner user. Re-running with
 * a new slug creates an additional tenant — useful when onboarding a
 * second firm later.
 *
 * Usage:
 *   php artisan lexa:install-primary-tenant            # interactive
 *   php artisan lexa:install-primary-tenant \
 *       --slug=lexa --name="Samir Group Legal" \
 *       --domain=lexi.deevar.cloud \
 *       --partner-email=mohamed@samir.legal \
 *       --partner-name="محمد سمير" \
 *       --partner-password='CHANGE-ME'
 */
class InstallPrimaryTenant extends Command
{
    protected $signature = 'lexa:install-primary-tenant
        {--slug=lexa : Tenant slug — becomes the primary key}
        {--name= : Display name of the firm}
        {--domain= : Hostname this tenant answers on (e.g. lexi.deevar.cloud)}
        {--partner-email= : Email of the initial partner user}
        {--partner-name= : Display name of the partner}
        {--partner-password= : Password for the partner (you should rotate after login)}
        {--force : Skip the interactive prompt}';

    protected $description = 'Create the primary tenant + first partner user (single-firm production deploy).';

    public function handle(): int
    {
        $slug = (string) $this->option('slug');
        $name = (string) ($this->option('name') ?: $this->ask('Display name of the firm'));
        $domain = (string) ($this->option('domain') ?: $this->ask('Hostname the firm answers on', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'lexi.deevar.cloud'));
        $partnerEmail = (string) ($this->option('partner-email') ?: $this->ask('Initial partner email'));
        $partnerName = (string) ($this->option('partner-name') ?: $this->ask('Initial partner name'));
        $partnerPassword = (string) ($this->option('partner-password') ?: $this->secret('Initial partner password'));

        try {
            $this->validate([
                'slug' => $slug,
                'name' => $name,
                'domain' => $domain,
                'partner_email' => $partnerEmail,
                'partner_name' => $partnerName,
                'partner_password' => $partnerPassword,
            ]);
        } catch (ValidationException $e) {
            // Errors already printed via $this->error inside validate().
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->table(['field', 'value'], [
                ['slug', $slug],
                ['name', $name],
                ['domain', $domain],
                ['partner email', $partnerEmail],
                ['partner name', $partnerName],
            ]);
            if (! $this->confirm('Create / update this tenant?', true)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $tenant = Tenant::query()->withoutGlobalScopes()->find($slug);
        if ($tenant) {
            $tenant->update(['name' => $name]);
            $this->info("Tenant [{$slug}] already exists — name refreshed.");
        } else {
            $tenant = Tenant::create([
                'id' => $slug,
                'name' => $name,
                'plan' => 'pro',
                'settings' => [],
                'branding' => [],
            ]);
            $this->info("Tenant [{$slug}] created.");
        }

        $domainRow = $tenant->domains()->firstOrCreate(['domain' => $domain]);
        $this->info("Domain [{$domain}] → tenant [{$slug}] mapped (#{$domainRow->id}).");

        // User doesn't use BelongsToTenant (central users can exist with
        // tenant_id=null) so no scope to bypass — direct query is fine.
        $existingPartner = User::query()
            ->where('tenant_id', $slug)
            ->where('email', $partnerEmail)
            ->first();

        if ($existingPartner) {
            $this->warn("Partner [{$partnerEmail}] already exists for [{$slug}] — leaving password untouched.");
        } else {
            User::query()->create([
                'tenant_id' => $slug,
                'name' => $partnerName,
                'email' => $partnerEmail,
                'password' => Hash::make($partnerPassword),
                'role' => UserRole::Partner->value,
                'locale' => 'ar',
                'email_verified_at' => now(),
            ]);
            $this->info("Partner [{$partnerEmail}] created. Log in at https://{$domain}/login");
        }

        $this->newLine();
        $this->line("✅ Done. Visit https://{$domain}/login");

        return self::SUCCESS;
    }

    /** @param  array<string, string>  $values */
    private function validate(array $values): void
    {
        $rules = [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{0,30}$/'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'partner_email' => ['required', 'email'],
            'partner_name' => ['required', 'string', 'max:255'],
            'partner_password' => ['required', 'string', 'min:8'],
        ];

        $validator = validator($values, $rules);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }
            throw new ValidationException($validator);
        }

        // Slug-format sanity check is duplicated as a friendlier error.
        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,30}$/', $values['slug'])) {
            throw ValidationException::withMessages(['slug' => 'Slug must be lowercase, start with a letter/digit, max 31 chars.']);
        }
    }
}
