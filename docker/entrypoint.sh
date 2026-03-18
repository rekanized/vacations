#!/bin/sh

set -eu

cd /app

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database
touch database/database.sqlite

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --no-interaction --force
fi

php artisan migrate --force --no-interaction

ROW_COUNT=$(php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::table('users')->count();" | tr -d '\r\n')

if [ "${ROW_COUNT:-0}" = "0" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link || true
php artisan optimize:clear

exec "$@"