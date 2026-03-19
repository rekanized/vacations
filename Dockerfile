FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libsqlite3-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite mbstring dom xml zip gd intl bcmath calendar \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-scripts --optimize

RUN chmod +x docker/entrypoint.sh \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 9000

ENTRYPOINT ["sh", "/app/docker/entrypoint.sh"]

CMD ["php-fpm"]