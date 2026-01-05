#!/bin/sh
set -e

cd /var/www

# Pastikan .env ada (untuk local/dev/testing)
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] .env not found, copying from .env.example"
  cp .env.example .env
fi

# Pastikan APP_KEY ada (untuk test container / dev)
# Kalau APP_KEY tidak diset via env dan .env belum punya APP_KEY, generate.
if [ -f .env ]; then
  if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] APP_KEY missing, generating..."
    php artisan key:generate --force || true
  fi
fi

# Optional migrations
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force || true
fi

# Optional storage link
if [ "${RUN_STORAGE_LINK}" = "true" ]; then
  php artisan storage:link || true
fi

# Permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
