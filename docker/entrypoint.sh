#!/usr/bin/env bash
set -e

# Optional: run migrations (aktifkan via App Setting RUN_MIGRATIONS=true)
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force || true
fi

# Optional: storage link (kalau dibutuhkan)
if [ "${RUN_STORAGE_LINK}" = "true" ]; then
  php artisan storage:link || true
fi

# Fix permissions (best-effort)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
