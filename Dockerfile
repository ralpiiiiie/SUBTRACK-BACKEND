FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        nginx \
        supervisor \
        curl \
    && docker-php-ext-install pdo_pgsql pgsql bcmath intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump \
    && mkdir -p storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/views \
                storage/logs \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["entrypoint.sh"]