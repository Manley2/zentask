#!/bin/sh
set -e

cd /var/www

# pastikan .env ada untuk local/dev
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] .env not found, copying from .env.example"
  cp .env.example .env
fi

# generate APP_KEY kalau belum ada
if [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
  if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] APP_KEY missing in .env, generating..."
    php artisan key:generate --force || true
  fi
fi

# OPTIONAL migrations
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force || true
fi

# OPTIONAL storage link
if [ "${RUN_STORAGE_LINK:-false}" = "true" ]; then
  php artisan storage:link || true
fi

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting supervisord (nginx + php-fpm + vite)..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
