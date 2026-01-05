#!/bin/sh
set -e

cd /var/www

# Optional: create .env if missing
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] .env not found, copying from .env.example"
  cp .env.example .env
fi

# Optional: generate APP_KEY if missing (only if env file exists)
if [ -f .env ]; then
  if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] APP_KEY missing, generating..."
    php artisan key:generate --force || true
  fi
fi

# Optional flags via App Settings
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force || true
fi

if [ "${RUN_STORAGE_LINK}" = "true" ]; then
  echo "[entrypoint] Creating storage link..."
  php artisan storage:link || true
fi

# Fix permissions (best effort)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
