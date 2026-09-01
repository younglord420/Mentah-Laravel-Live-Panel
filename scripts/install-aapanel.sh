#!/usr/bin/env bash
# Instalasi Mentah Laravel Live Panel di aaPanel
# Jalankan dari root project: bash scripts/install-aapanel.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

AUTO_YES=0
SKIP_SEED=0
WEB_USER="${WEB_USER:-www}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --yes|-y) AUTO_YES=1; shift ;;
        --skip-seed) SKIP_SEED=1; shift ;;
        --web-user) WEB_USER="$2"; shift 2 ;;
        *) echo "Opsi tidak dikenal: $1"; exit 1 ;;
    esac
done

confirm() {
    if [[ "$AUTO_YES" -eq 1 ]]; then
        return 0
    fi
    read -r -p "$1 [y/N]: " ans
    [[ "${ans,,}" == "y" || "${ans,,}" == "yes" ]]
}

log() { echo "==> $*"; }
die() { echo "ERROR: $*" >&2; exit 1; }

log "Project: $ROOT"

# PHP
PHP_BIN="${PHP_BIN:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    if [[ -x /www/server/php/83/bin/php ]]; then
        PHP_BIN=/www/server/php/83/bin/php
    else
        die "PHP tidak ditemukan. Set PHP_BIN atau install PHP 8.3 di aaPanel."
    fi
fi

PHP_VERSION="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
log "PHP: $PHP_BIN ($PHP_VERSION)"
[[ "$("$PHP_BIN" -r 'echo version_compare(PHP_VERSION, "8.3.0", ">=") ? "1" : "0";')" == "1" ]] \
    || die "Butuh PHP 8.3+. Versi sekarang: $PHP_VERSION"

# Composer
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    if [[ -x /usr/bin/composer ]]; then
        COMPOSER_BIN=/usr/bin/composer
    else
        die "Composer tidak ditemukan. Install dari aaPanel App Store."
    fi
fi
log "Composer: $COMPOSER_BIN"

# .env
if [[ ! -f .env ]]; then
    log "Membuat .env dari .env.example"
    cp .env.example .env
fi

# Prompt DB jika kosong
set_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}

if [[ -z "${DB_DATABASE:-}" ]]; then
    if [[ "$AUTO_YES" -eq 0 ]]; then
        read -r -p "DB_DATABASE [livepanel]: " DB_DATABASE
    fi
    DB_DATABASE="${DB_DATABASE:-livepanel}"
fi
if [[ -z "${DB_USERNAME:-}" ]]; then
    if [[ "$AUTO_YES" -eq 0 ]]; then
        read -r -p "DB_USERNAME [livepanel]: " DB_USERNAME
    fi
    DB_USERNAME="${DB_USERNAME:-livepanel}"
fi
if [[ -z "${DB_PASSWORD:-}" ]]; then
    if [[ "$AUTO_YES" -eq 0 ]]; then
        read -r -s -p "DB_PASSWORD: " DB_PASSWORD
        echo
    fi
fi
if [[ -z "${APP_URL:-}" ]]; then
    if [[ "$AUTO_YES" -eq 0 ]]; then
        read -r -p "APP_URL [https://domain-kamu.com]: " APP_URL
    fi
    APP_URL="${APP_URL:-https://domain-kamu.com}"
fi

set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "$APP_URL"
set_env DB_CONNECTION mysql
set_env DB_HOST "${DB_HOST:-127.0.0.1}"
set_env DB_PORT "${DB_PORT:-3306}"
set_env DB_DATABASE "$DB_DATABASE"
set_env DB_USERNAME "$DB_USERNAME"
set_env DB_PASSWORD "${DB_PASSWORD:-}"
set_env SESSION_DRIVER database
set_env CACHE_STORE database
set_env QUEUE_CONNECTION database

log "composer install --no-dev"
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

# Frontend build
if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
  if [[ -d node_modules ]] || confirm "Jalankan npm install && npm run build?"; then
    log "npm install"
    npm install --ignore-scripts
    log "npm run build"
    npm run build
  fi
else
  echo "WARN: npm tidak ada — lewati build frontend. Upload public/build manual atau install Node.js di aaPanel."
fi

log "php artisan key:generate"
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    "$PHP_BIN" artisan key:generate --force
fi

log "php artisan migrate --force"
"$PHP_BIN" artisan migrate --force

if [[ "$SKIP_SEED" -eq 0 ]]; then
    if confirm "Jalankan db:seed (admin@example.com / password)?"; then
        "$PHP_BIN" artisan db:seed --force
    fi
fi

log "Permission storage & bootstrap/cache"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
if id "$WEB_USER" &>/dev/null; then
    chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache 2>/dev/null || true
else
    echo "WARN: user '$WEB_USER' tidak ada — skip chown. Sesuaikan WEB_USER=www jika perlu."
fi

log "Cache production"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo ""
echo "=============================================="
echo " Instalasi selesai"
echo "=============================================="
echo ""
echo "1. Document root Nginx HARUS ke:"
echo "   $ROOT/public"
echo ""
echo "2. Pasang SSL di aaPanel (Let's Encrypt)"
echo ""
echo "3. Tambah cron (setiap 1 menit):"
echo "   cd $ROOT && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "4. Login admin:"
echo "   ${APP_URL%/}/admin"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
echo "5. Baca panduan lengkap: INSTALL_AAPANEL.md"
echo ""
