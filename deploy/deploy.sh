#!/usr/bin/env bash
# LEXA — per-release deploy script. Run as the cPanel user (NOT root)
# from the repo root: `./deploy/deploy.sh`.
#
# Pulls the latest code, installs dependencies, migrates, caches config,
# and tells supervisor to bounce the Horizon worker.
#
# Safe to re-run on every release. First-run differences:
#   - `php artisan key:generate` if APP_KEY is empty
#   - `php artisan storage:link` if the symlink is missing

set -euo pipefail

cd "$(dirname "$0")/.."
APP_ROOT="$(pwd)"
echo "==> Deploying LEXA from ${APP_ROOT}"

if [[ ! -f .env ]]; then
    echo "!! .env missing. Copy .env.production.example to .env and edit it first." >&2
    exit 1
fi

# Detect the cPanel PHP we should use. Override with PHP_BIN=/path/to/php.
PHP_BIN="${PHP_BIN:-$(ls /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php 2>/dev/null | head -n1 || command -v php)}"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer || echo composer)}"

echo "    PHP: ${PHP_BIN}"
${PHP_BIN} --version | head -n1

echo "==> [1/9] git pull (skip if not a git checkout)"
if [[ -d .git ]]; then
    git fetch --all --prune
    git reset --hard "${DEPLOY_REF:-origin/main}"
fi

echo "==> [2/9] composer install --no-dev --optimize-autoloader"
${PHP_BIN} ${COMPOSER_BIN} install --no-dev --optimize-autoloader --no-interaction

echo "==> [3/9] npm ci + build (skipped if no package.json change since last build)"
if command -v npm >/dev/null 2>&1; then
    if [[ ! -f public/build/manifest.json ]] || [[ package-lock.json -nt public/build/manifest.json ]]; then
        npm ci
        npm run build
    else
        echo "    public/build is fresh, skipping npm build"
    fi
else
    echo "    !! npm not on PATH. Build the front-end locally and rsync public/build/ to the server."
fi

echo "==> [4/9] APP_KEY"
if ! grep -q '^APP_KEY=base64:' .env; then
    ${PHP_BIN} artisan key:generate --force
fi

echo "==> [5/9] storage:link"
[[ -L public/storage ]] || ${PHP_BIN} artisan storage:link

echo "==> [6/9] migrate --force"
${PHP_BIN} artisan migrate --force

# Reference data only — never seed demo tenants in production.
echo "==> [7/9] seed reference data"
${PHP_BIN} artisan db:seed --class=Database\\Seeders\\CourtTypeSeeder --force
${PHP_BIN} artisan db:seed --class=Database\\Seeders\\CourtSeeder --force
${PHP_BIN} artisan db:seed --class=Database\\Seeders\\CaseTypeSeeder --force
${PHP_BIN} artisan db:seed --class=Database\\Seeders\\RequestTypeSeeder --force
${PHP_BIN} artisan db:seed --class=Database\\Seeders\\JudgmentTypeSeeder --force

echo "==> [8/9] cache config / routes / views"
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
${PHP_BIN} artisan event:cache

# Refresh permissions on writable dirs (cPanel sometimes resets these on restore).
chmod -R u+rwX,g+rX storage bootstrap/cache

echo "==> [9/9] restart Horizon worker (via supervisor)"
if command -v sudo >/dev/null 2>&1 && sudo -n supervisorctl restart lexa-horizon:* >/dev/null 2>&1; then
    echo "    Horizon worker restarted."
else
    echo "    !! Could not restart supervisor as the cPanel user. Ask root to run:"
    echo "       sudo supervisorctl restart lexa-horizon:*"
fi

cat <<EOF

==================================================================
✅ LEXA deployed.

Sanity check:
  curl -sI https://${APP_URL:-lexi.deevar.cloud}/up | head -1
  curl -s  https://${APP_URL:-lexi.deevar.cloud}/up

Tenant smoke test (path mode):
  https://lexi.deevar.cloud/t/<your-tenant-slug>/login
==================================================================
EOF
