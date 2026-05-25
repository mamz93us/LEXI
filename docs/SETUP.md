# LEXA — Local development setup

This guide walks you through getting LEXA running on a Windows development machine. The same Docker Compose stack works on macOS and Linux; only the hosts-file path and editor-as-admin steps differ.

## Prerequisites

| Tool | Minimum | Notes |
|---|---|---|
| PHP | 8.2+ | 8.3+ recommended. XAMPP, Laravel Herd, or a standalone install all work. |
| Composer | 2.5+ | https://getcomposer.org |
| Node.js | 20+ | For Vite asset build. |
| npm | 10+ | Ships with Node. |
| Docker Desktop | latest | https://www.docker.com/products/docker-desktop — required for Postgres+pgvector, Redis, Meilisearch. |
| Git | latest | |

Verify:
```powershell
php --version
composer --version
node --version
npm --version
docker --version
```

## 1. Clone and install

```powershell
git clone <repo-url> LEXI
cd LEXI
composer install
npm install
```

## 2. Bring up infrastructure

```powershell
docker compose up -d
docker compose ps     # all services should be "healthy"
```

This starts:
- **postgres** — Postgres 16 with the `vector` extension pre-installed (image: `pgvector/pgvector:pg16`), on port `5432`.
- **redis** — Redis 7 on port `6379`.
- **meilisearch** — Meilisearch on port `7700`.

Data persists in named Docker volumes. To wipe and start fresh:
```powershell
docker compose down -v
```

## 3. Environment file

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

The defaults in `.env.example` point at the Docker Compose services on `127.0.0.1`. Adjust `ANTHROPIC_API_KEY` and the S3 credentials when those become real; placeholders are fine for Phase 1.

## 4. Subdomain routing (recommended) — edit your hosts file

Open Notepad **as Administrator**, then open:
```
C:\Windows\System32\drivers\etc\hosts
```
Add these lines at the bottom:
```
127.0.0.1  lexa.test
127.0.0.1  samir.lexa.test
127.0.0.1  demo.lexa.test
```
Save. Flush DNS cache (PowerShell as Admin):
```powershell
ipconfig /flushdns
```

You can now reach `http://samir.lexa.test:8000` and `http://demo.lexa.test:8000` once the app is running.

### Path-based fallback (no hosts edit needed)

If you can't edit the hosts file (locked-down machine, etc.), every tenant is also reachable at `http://localhost:8000/t/{slug}/...`. Subdomain routing is preferred — it matches production — but path routing exists so new contributors aren't blocked.

## 5. Migrate and seed

```powershell
php artisan migrate --seed
```

Seeded demo tenants:

| Tenant | Subdomain | Partner login |
|---|---|---|
| Samir Group Legal | `samir.lexa.test` | `partner@samir.test` / `lexa-dev` |
| Demo Firm | `demo.lexa.test` | `partner@demo.test` / `lexa-dev` |

## 6. Run the dev server

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

In a separate terminal, run Vite:
```powershell
npm run dev
```

For background jobs (will be exercised more in Phase 2):
```powershell
php artisan horizon
```

Visit `http://samir.lexa.test:8000` and log in.

## 7. Run tests and linter

```powershell
php artisan test                  # Pest
vendor/bin/pint --test            # check formatting without changing files
vendor/bin/pint                   # auto-fix formatting
```

## 8. Resetting demo data

```powershell
php artisan migrate:fresh --seed
```

## Common issues

**"Could not find driver" on `php artisan migrate`** — your PHP is missing the `pdo_pgsql` extension. XAMPP ships it; if you have a stripped install, enable it in `php.ini` (uncomment `extension=pdo_pgsql`) and restart.

**"Connection refused" to Postgres** — `docker compose ps` and confirm the `postgres` service is healthy. First boot takes ~10–30 seconds.

**Subdomain returns "404 Not Found"** — confirm the hosts-file edit is saved and you flushed DNS. Test with `ping samir.lexa.test` — it should resolve to `127.0.0.1`.

**Tenant identification fails after login** — the user record's `tenant_id` must match the tenant resolved from the subdomain. The demo seeder handles this correctly; manual user creation must too.
