# LEXA — cPanel / WHM VPS deployment

Production target: **`lexi.deevar.cloud`** on an AlmaLinux 9 cPanel+WHM
VPS with root SSH access.

> **Time budget for a first deploy: ~45 minutes.** All commands are
> copy-paste. Anything destructive is called out before it runs.

## 0. Prerequisites checklist

Before you start, confirm in WHM:
- [ ] PHP **8.2 or newer** installed via EasyApache 4 (WHM ▸ EasyApache 4 ▸ Customize ▸ PHP). EasyApache provides php82, php83, etc. as `ea-phpXX`.
- [ ] The cPanel account for `lexi.deevar.cloud` exists (`WHM ▸ List Accounts`). We'll call its username `lexa` in the examples — substitute your real username throughout.
- [ ] DNS for `lexi.deevar.cloud` already resolves to this server's public IP (you confirmed this is working).
- [ ] AutoSSL or a Let's Encrypt cert covers `lexi.deevar.cloud`.
- [ ] Outbound HTTPS is open (to fetch `composer` packages, `npm` packages, the Anthropic API, and the embedding provider).

## 1. SSH in as root and run the one-time provisioning

```bash
# From your laptop:
ssh root@lexi.deevar.cloud
```

Clone the repo somewhere root can reach it (we'll move the working copy
to the cPanel user's home directory in §2):

```bash
cd /opt
git clone <your-git-url> lexa-source
cd lexa-source
chmod +x deploy/*.sh
```

Set the secrets the provisioner needs and run it:

```bash
export CPANEL_USER=lexa
export DOMAIN=lexi.deevar.cloud
export DB_NAME=lexa
export DB_USER=lexa
export DB_PASSWORD="$(openssl rand -base64 24)"
export MEILI_KEY="$(openssl rand -hex 32)"

echo "DB_PASSWORD=$DB_PASSWORD"
echo "MEILI_KEY=$MEILI_KEY"
# COPY THESE TWO LINES somewhere safe — you need them in .env in §2.

./deploy/provision.sh
```

What `provision.sh` does (each step is idempotent — safe to re-run):
1. Installs base OS packages (`git`, `curl`, `firewalld`, …)
2. Installs **PostgreSQL 16** + **pgvector** from the official PGDG repo and runs `initdb`
3. Adds `pg_hba.conf` rules so `127.0.0.1` can connect with `scram-sha-256`
4. Creates the `lexa` role and `lexa` database, enables `CREATE EXTENSION vector` inside it
5. Installs **Redis 7**, binds it to `127.0.0.1`
6. Installs **Meilisearch**, creates a `meilisearch` system user, installs the systemd unit from `deploy/meilisearch.service`, starts it
7. Installs **supervisord** and the `lexa-horizon` worker config from `deploy/horizon.supervisor.conf`
8. Installs **PHP extensions** LEXA needs against the EasyApache PHP build (`pgsql`, `redis`, `gd`, `intl`, `mbstring`, `zip`, `curl`, `bcmath`, `pcntl`, `posix`, `sodium`)
9. Opens HTTP and HTTPS in `firewalld`

Verify each service:

```bash
sudo -iu postgres psql -d lexa -c "SELECT extname FROM pg_extension WHERE extname='vector';"
redis-cli ping                # → PONG
systemctl status meilisearch  # → active (running)
supervisorctl status          # → lexa-horizon:lexa-horizon_00  STOPPED (we start it after deploy)
```

## 2. Pull the app into the cPanel user's home

```bash
sudo -iu lexa            # become the cPanel user
cd ~
git clone <your-git-url> lexa
cd lexa

# Copy the production env template and fill it in.
cp .env.production.example .env
nano .env
```

In `.env`, set at minimum:

| Key | Value |
|---|---|
| `APP_KEY` | Leave blank — `deploy.sh` generates it on first run |
| `DB_PASSWORD` | The `DB_PASSWORD` you printed in §1 |
| `MEILISEARCH_KEY` | The `MEILI_KEY` you printed in §1 |
| `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | Your SMTP relay (SES / Mailgun / cPanel mail) |
| `ANTHROPIC_API_KEY` | Leave blank for now — fill once the firm signs the zero-retention agreement with Anthropic |
| `EMBEDDINGS_DRIVER` | Keep `null` until the retrieval-quality eval picks a real model |

Everything else has a sensible default for `lexi.deevar.cloud`.

Now deploy:

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

`deploy.sh` will:
1. `git pull` to `origin/main` (skip if you're already on the right commit)
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build` (only if `package-lock.json` is newer than the existing build)
4. `php artisan key:generate` if APP_KEY is empty
5. `php artisan storage:link`
6. `php artisan migrate --force`
7. Seed **reference data only** (court types, courts, case types, request types, judgment types — never DemoTenantsSeeder in production)
8. `php artisan config:cache route:cache view:cache event:cache`
9. Restart the Horizon worker via supervisor

## 3. Point the cPanel domain at `public/`

In WHM as root:

```bash
# Option A — recommended: change the docroot for the domain.
# Replace the cPanel user and zone as appropriate.
sudo /usr/local/cpanel/bin/setsiteip -u lexa lexi.deevar.cloud
# (the user-data file is at /var/cpanel/userdata/lexa/lexi.deevar.cloud)
```

Or via WHM UI:

1. `WHM ▸ Web Host Manager ▸ Apache Configuration ▸ Include Editor`
2. Add a `Pre VirtualHost Include` for `*All Versions*`:

   ```apache
   <Directory "/home/lexa/lexa/public">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. `WHM ▸ Account Functions ▸ Modify an Account` for the `lexa` user → set
   "Document Root" to `/home/lexa/lexa/public`. Save.
4. Rebuild Apache config + restart:

   ```bash
   /scripts/rebuildhttpdconf
   /scripts/restartsrv_httpd
   ```

Then enable AutoSSL:

1. `WHM ▸ SSL/TLS ▸ Manage AutoSSL ▸ Manage Users` → check `lexa`, save.
2. Run `cpsrvd` AutoSSL pass:

   ```bash
   /usr/local/cpanel/bin/autossl_check --user=lexa
   ```

Within ~2 minutes the cert is live.

## 4. Sanity-check the live deploy

```bash
# From any machine:
curl -sI https://lexi.deevar.cloud/up      # → HTTP/2 200
curl -s  https://lexi.deevar.cloud/up      # → JSON health payload

# A central landing page should render:
curl -s https://lexi.deevar.cloud/ | head -20

# Path-based tenant routing — works immediately (no wildcard needed).
# Replace `samir` with your real tenant slug once you create one.
curl -sI https://lexi.deevar.cloud/t/samir/dashboard
```

Then in a browser:
1. Visit `https://lexi.deevar.cloud/` — central landing page.
2. Once you create your first tenant in the admin (next section),
   sign in at `https://lexi.deevar.cloud/t/<slug>/login`.

## 5. Create your first real tenant

There's no public registration in v1 — tenants are created by the
super-admin. SSH back in as the cPanel user:

```bash
cd ~/lexa
php artisan tinker
```

Inside tinker:

```php
$tenant = App\Models\Tenant::create([
    'id' => 'samir-real',           // becomes the slug (no spaces, lowercase)
    'name' => 'Samir Group Legal',
    'plan' => 'pro',
    'settings' => [],
    'branding' => [],
]);
$tenant->domains()->create(['domain' => 'samir-real.lexi.deevar.cloud']);

App\Models\User::create([
    'tenant_id' => 'samir-real',
    'name'      => 'محمد سمير',
    'email'     => 'mohamed@samir.legal',
    'password'  => Hash::make('CHANGE-ME-NOW'),
    'role'      => App\Enums\UserRole::Partner->value,
    'locale'    => 'ar',
    'email_verified_at' => now(),
]);
```

The partner can now log in at
`https://lexi.deevar.cloud/t/samir-real/login`.

## 6. Cron jobs

As the cPanel user (`crontab -e -u lexa`):

```cron
# Laravel scheduler — heartbeat every minute, cheap when idle.
* * * * * /opt/cpanel/ea-php82/root/usr/bin/php /home/lexa/lexa/artisan schedule:run >> /home/lexa/lexa/storage/logs/cron.log 2>&1
```

Horizon is already running under supervisord — no cron entry needed for
queue processing.

## 7. (Optional, when ready) Enable subdomain tenant routing

You confirmed `lexi.deevar.cloud` resolves correctly. To support
`samir.lexi.deevar.cloud` style URLs:

1. **DNS** — add a wildcard A record:
   `*.lexi.deevar.cloud  A  <this server's IP>`
   (do this at your DNS provider for `deevar.cloud`)
2. **Apache vhost** — set the cPanel account to accept the wildcard:
   - `WHM ▸ Account Functions ▸ Modify an Account` for `lexa`
   - Add `*.lexi.deevar.cloud` as an additional parked / addon domain
   - Or add a `VirtualHost` block via the Include Editor that aliases
     `*.lexi.deevar.cloud` to the same docroot.
3. **SSL** — wildcards need DNS-01 validation. Two options:
   - Put the domain behind Cloudflare with "Full (strict)" — Cloudflare's
     edge cert covers the wildcard, your origin uses the existing
     AutoSSL cert.
   - Or run `certbot certonly --dns-cloudflare -d '*.lexi.deevar.cloud'`
     (or similar plugin for your DNS host), then point the cPanel SSL
     setting at the resulting cert.

After this, sign-in URLs become `https://samir.lexi.deevar.cloud/login`.

## 8. Updating an existing deploy

```bash
sudo -iu lexa
cd ~/lexa
./deploy/deploy.sh
```

That's it. Migrations run, caches refresh, Horizon restarts.

## 9. Troubleshooting

**`SQLSTATE[08006] could not connect`** — Postgres isn't accepting your
credentials. Check `~/lexa/.env`'s `DB_*` values match what `provision.sh`
created, and that you reloaded after editing `pg_hba.conf`:
`sudo systemctl restart postgresql-16`.

**"500 Internal Server Error" with no Laravel debug page** —
`APP_DEBUG=false` is set correctly. Check `~/lexa/storage/logs/laravel.log`
for the trace. Common cause: storage dir not writable —
`chmod -R u+rwX,g+rX ~/lexa/storage ~/lexa/bootstrap/cache`.

**Mixed-content warnings (HTTPS page loading HTTP assets)** — Laravel
is generating http:// URLs because it doesn't see the proxy. Confirm
`TRUSTED_PROXIES=*` in `.env` and that `cPanel ▸ Apache mod_remoteip` is
forwarding `X-Forwarded-Proto`.

**Horizon shows "Inactive" in the dashboard** — the worker isn't running.
`sudo supervisorctl status lexa-horizon:*` should show RUNNING. If it
says STOPPED, run `sudo supervisorctl start lexa-horizon:*`. If it keeps
flapping, tail `~/lexa/storage/logs/horizon.log`.

**Meilisearch refusing requests** — `systemctl status meilisearch` —
must be `active`. Confirm `MEILISEARCH_KEY` in `.env` matches the value
in `/etc/systemd/system/meilisearch.service`.

**"vector type does not exist" during migrate** — `pgvector_16` package
didn't install. Re-run as root:
`dnf install -y pgvector_16 && sudo -iu postgres psql -d lexa -c "CREATE EXTENSION IF NOT EXISTS vector"`.
