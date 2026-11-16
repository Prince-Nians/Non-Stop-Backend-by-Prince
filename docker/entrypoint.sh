#!/bin/bash
# Runs on every container start.
#   RUN_MIGRATIONS=true  -> php artisan migrate --force   (the backend owns the database schema)
#   RUN_SEED=true        -> php artisan db:seed --force   (loads default content only if the database is empty; never overwrites edits)
set -e
cd /var/www/html

# Zero-config on Render: a stable APP_KEY is derived from APP_KEY_SEED (Render can generate that value),
# and APP_URL defaults to the service's own public address.
if [ -z "${APP_KEY:-}" ] && [ -n "${APP_KEY_SEED:-}" ]; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY_SEED"), true));')"
fi
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

php artisan config:cache
php artisan view:cache

if [ "${PUBLIC_DISK_DRIVER:-local}" = "local" ]; then
  php artisan storage:link --force || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${RUN_SEED:-false}" = "true" ]; then
  # In the background: the first run copies ~20 MB of demo files into the database, which can take a couple of
  # minutes. Apache starts straight away so Render sees the port open; the API fills up as the seed finishes.
  # Re-running is safe: the seeder skips once it has finished, and resumes if it was interrupted.
  (php artisan db:seed --force > /proc/1/fd/1 2>&1 || true) &
fi

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
