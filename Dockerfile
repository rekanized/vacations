FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_sqlite mbstring dom xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN chmod +x docker/entrypoint.sh \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 9000

ENTRYPOINT ["/app/docker/entrypoint.sh"]

CMD ["php-fpm"]