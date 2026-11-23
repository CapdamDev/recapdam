#!/bin/sh
# Prepares the app on every container start, then hands over to Apache.
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Generate one with:" >&2
    echo "  docker compose -f docker-compose.prod.yml run --rm --no-deps --entrypoint php app artisan key:generate --show" >&2
    exit 1
fi

# storage/app is a volume; on first start it is empty, so recreate the folders the app expects.
mkdir -p storage/app/public storage/app/private storage/fonts storage/logs \
    storage/framework/cache/data storage/framework/sessions storage/framework/views

# Wait for the database container (up to ~60 s).
tries=0
until php artisan db:show >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "ERROR: database not reachable at ${DB_HOST}:${DB_PORT}." >&2
        php artisan db:show >&2 || true
        exit 1
    fi
    sleep 2
done

php artisan migrate --force
php artisan optimize
php artisan filament:optimize

# Commands above ran as root; Apache runs as www-data.
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
