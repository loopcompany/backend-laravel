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
php artisan config:clear
php artisan view:clear

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
