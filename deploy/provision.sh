#!/usr/bin/env bash
# LEXA — one-time server provisioning for a cPanel/WHM VPS on AlmaLinux 9.
#
# Run as ROOT (or via sudo) once on the host. Idempotent — safe to re-run.
# Installs: PostgreSQL 16 + pgvector, Redis 7, Meilisearch, supervisord,
# and PHP extensions LEXA needs.
#
# After this finishes, switch to the cPanel user and run deploy/deploy.sh.

set -euo pipefail

if [[ ${EUID} -ne 0 ]]; then
    echo "This script must be run as root (or via sudo)." >&2
    exit 1
fi

CPANEL_USER="${CPANEL_USER:-}"
DOMAIN="${DOMAIN:-lexi.deevar.cloud}"
DB_NAME="${DB_NAME:-lexa}"
DB_USER="${DB_USER:-lexa}"
DB_PASSWORD="${DB_PASSWORD:-}"
MEILI_KEY="${MEILI_KEY:-}"

if [[ -z "${CPANEL_USER}" ]]; then
    echo "Set CPANEL_USER=<your-cpanel-username> before running. Aborting." >&2
    exit 1
fi
if [[ -z "${DB_PASSWORD}" ]]; then
    echo "Set DB_PASSWORD=<strong-pg-password> before running. Aborting." >&2
    exit 1
fi
if [[ -z "${MEILI_KEY}" ]]; then
    echo "Set MEILI_KEY=<random-32-char-secret> before running. Aborting." >&2
    exit 1
fi

echo "==> [1/8] Detect OS"
. /etc/os-release
echo "    OS: ${PRETTY_NAME}"

echo "==> [2/8] Base packages"
dnf install -y epel-release || true
dnf install -y curl wget tar gzip git which lsof firewalld policycoreutils-python-utils

echo "==> [3/8] PostgreSQL 16 + pgvector"
if ! command -v psql >/dev/null 2>&1; then
    dnf install -y https://download.postgresql.org/pub/repos/yum/reporpms/EL-9-x86_64/pgdg-redhat-repo-latest.noarch.rpm
    dnf -qy module disable postgresql || true
    dnf install -y postgresql16-server postgresql16-contrib pgvector_16
    /usr/pgsql-16/bin/postgresql-16-setup initdb
    systemctl enable --now postgresql-16
else
    echo "    postgres already present, skipping install"
fi

# Allow password auth on localhost so the Laravel app can connect over TCP.
PG_HBA="/var/lib/pgsql/16/data/pg_hba.conf"
if ! grep -q "lexa-deploy" "${PG_HBA}"; then
    cat >> "${PG_HBA}" <<EOF

# lexa-deploy
host    ${DB_NAME}   ${DB_USER}   127.0.0.1/32   scram-sha-256
host    ${DB_NAME}   ${DB_USER}   ::1/128        scram-sha-256
EOF
    systemctl restart postgresql-16
fi

# Create role + database if missing, enable pgvector inside the db.
sudo -iu postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1 \
    || sudo -iu postgres psql -c "CREATE ROLE ${DB_USER} WITH LOGIN PASSWORD '${DB_PASSWORD}'"

sudo -iu postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1 \
    || sudo -iu postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}"

sudo -iu postgres psql -d "${DB_NAME}" -c "CREATE EXTENSION IF NOT EXISTS vector"

echo "==> [4/8] Redis 7"
if ! command -v redis-server >/dev/null 2>&1; then
    dnf install -y redis
    sed -i 's/^bind .*/bind 127.0.0.1/' /etc/redis/redis.conf
    systemctl enable --now redis
fi

echo "==> [5/8] Meilisearch"
if ! command -v meilisearch >/dev/null 2>&1; then
    curl -L https://install.meilisearch.com -o /tmp/meili-install.sh
    bash /tmp/meili-install.sh
    install -m 0755 meilisearch /usr/local/bin/meilisearch
    rm -f meilisearch /tmp/meili-install.sh
fi

useradd -r -s /sbin/nologin meilisearch 2>/dev/null || true
mkdir -p /var/lib/meilisearch
chown meilisearch:meilisearch /var/lib/meilisearch

install -m 0644 "$(dirname "$0")/meilisearch.service" /etc/systemd/system/meilisearch.service
sed -i "s|__MEILI_KEY__|${MEILI_KEY}|g" /etc/systemd/system/meilisearch.service
systemctl daemon-reload
systemctl enable --now meilisearch

echo "==> [6/8] Supervisor (for Horizon worker)"
dnf install -y supervisor
systemctl enable --now supervisord

install -m 0644 "$(dirname "$0")/horizon.supervisor.conf" /etc/supervisord.d/lexa-horizon.ini
sed -i "s|__CPANEL_USER__|${CPANEL_USER}|g" /etc/supervisord.d/lexa-horizon.ini
supervisorctl reread || true
supervisorctl update || true

echo "==> [7/8] PHP extensions via EasyApache"
# Detect the active PHP 8.2+ binary that cPanel installed. EasyApache packages
# look like ea-php82, ea-php83, etc. We enable the extensions LEXA needs.
PHP_BIN="$(ls /opt/cpanel/ea-php82/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php 2>/dev/null | head -n1 || true)"
if [[ -z "${PHP_BIN}" ]]; then
    echo "    !! No EasyApache PHP 8.2+ found. Install via WHM > EasyApache 4 first." >&2
else
    PHP_VER="$(${PHP_BIN} -r 'echo PHP_MAJOR_VERSION . PHP_MINOR_VERSION;')"
    echo "    Using PHP ${PHP_VER}"
    dnf install -y \
        ea-php${PHP_VER}-php-pgsql \
        ea-php${PHP_VER}-php-pdo \
        ea-php${PHP_VER}-php-pecl-redis \
        ea-php${PHP_VER}-php-gd \
        ea-php${PHP_VER}-php-intl \
        ea-php${PHP_VER}-php-mbstring \
        ea-php${PHP_VER}-php-zip \
        ea-php${PHP_VER}-php-curl \
        ea-php${PHP_VER}-php-bcmath \
        ea-php${PHP_VER}-php-exif \
        ea-php${PHP_VER}-php-fileinfo \
        ea-php${PHP_VER}-php-pcntl \
        ea-php${PHP_VER}-php-posix \
        ea-php${PHP_VER}-php-sodium 2>&1 | tail -3 || true
fi

echo "==> [8/8] Firewall — restrict the new services to localhost"
# Postgres / Redis / Meilisearch are only used by the local PHP process.
# Don't punch holes in the firewall for them.
firewall-cmd --permanent --add-service=http     >/dev/null 2>&1 || true
firewall-cmd --permanent --add-service=https    >/dev/null 2>&1 || true
firewall-cmd --reload                           >/dev/null 2>&1 || true

cat <<EOF

==================================================================
✅ Server provisioned.

Now switch to the cPanel user and run the application deploy:

    sudo -iu ${CPANEL_USER}
    cd ~
    git clone <git-url> lexa
    cd lexa
    cp .env.production.example .env
    # edit .env — set APP_KEY (run php artisan key:generate), DB password,
    # MEILI master key, ANTHROPIC_API_KEY when ready.
    ./deploy/deploy.sh

Then in WHM:
  - Set the document root for ${DOMAIN} to /home/${CPANEL_USER}/lexa/public
  - Enable AutoSSL on ${DOMAIN}
  - (Optional, for subdomain tenant routing) add a wildcard A record:
        *.${DOMAIN}  →  this server's IP
==================================================================
EOF
