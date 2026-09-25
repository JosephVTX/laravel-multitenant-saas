#!/bin/sh
set -e

APP_PORT="${PORT:-8000}"

php artisan config:cache
php artisan route:cache
php artisan view:cache >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running database migrations..."
    if [ "${RUN_SEED:-true}" = "true" ]; then
        php artisan migrate --force --seed
    else
        php artisan migrate --force
    fi
fi

if [ "${RUN_STORAGE_LINK:-false}" = "true" ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

echo "Starting Octane (FrankenPHP) on 0.0.0.0:${APP_PORT}..."

exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port="${APP_PORT}" \
    --workers="${OCTANE_WORKERS:-auto}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"
