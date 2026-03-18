#!/bin/bash

# Install dependencies
php /opt/php/composer/composer.phar install --no-dev --optimize-autoloader

# Set permissions
chmod -R 775 storage bootstrap/cache

php artisan migrate --force --no-interaction

ROW_COUNT=$(php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::table('users')->count()")
if [ "$ROW_COUNT" -eq "0" ]; then
    echo "Table is empty ($ROW_COUNT rows). Running First Time Seeder..."
    
    php artisan db:seed --force --no-interaction
    
    echo "Seeding completed."
else
    echo "Table already has data ($ROW_COUNT rows). Skipping seeder."
fi

php artisan storage:link || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache