#!/bin/sh
set -e

cd /var/www

# Create .env only if missing (local/dev convenience)
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[entrypoint] .env not found, copying from .env.example"
  cp .env.example .env
fi

# IMPORTANT (Azure best practice):
# Jangan generate APP_KEY otomatis di production.
# APP_KEY harus diset dari Azure App Settings / pipeline secret.
# Kalau kamu mau enable untuk local saja, pakai flag RUN_KEYGEN=true.
if [ "${RUN_KEYGEN}" = "true" ]; then
  if [ -f .env ] && ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] RUN_KEYGEN=true and APP_KEY missing, generating..."
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
