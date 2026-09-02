#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    sqlite_database="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$sqlite_database")"
    touch "$sqlite_database"
    chown www-data:www-data "$sqlite_database"
fi

if [ -z "${APP_KEY:-}" ] && ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan package:discover --ansi
php artisan migrate --force --no-interaction
php artisan storage:link --force 2>/dev/null || true

# Publish Filament + plugin CSS/JS to public/css and public/js. The image is
# built with `composer install --no-scripts`, so the post-autoload-dump hook
# (filament:upgrade) never runs and the admin panel would load unstyled.
php artisan filament:assets

# `php artisan serve` only forwards a small allowlist of env vars to its PHP
# worker processes, so container-provided values (DB_*, etc.) never reach the
# request handler and it falls back to .env. Caching config bakes the resolved
# values in so the served app uses the real configuration.
php artisan config:clear
php artisan view:clear
php artisan config:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
