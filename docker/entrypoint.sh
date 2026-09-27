#!/usr/bin/env sh
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force --no-interaction
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "[entrypoint] Seeding platform settings and super admin..."
    php artisan db:seed --class=PlatformSettingSeeder --force --no-interaction
    php artisan db:seed --class=SuperAdminSeeder --force --no-interaction
fi

exec "$@"
