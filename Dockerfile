# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# base — shared PHP-FPM runtime + extensions + composer + entrypoint
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# ---------------------------------------------------------------------------
# vendor — dependencies baked onto the fast image layer (includes dev deps so
# `php artisan test` works inside the dev container too).
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ---------------------------------------------------------------------------
# development — code is bind-mounted at runtime; vendor is baked into the
# image so it lives on the fast container layer, NOT the (slow, host-bound)
# bind mount. This is what keeps local request latency sane on hosts where
# bind-mount syscalls (stat/opendir/file_exists) are expensive (e.g. Windows).
# ---------------------------------------------------------------------------
FROM base AS development

COPY --from=vendor /var/www/html/vendor ./vendor

# The dev entrypoint regenerates the optimized classmap after the source bind
# mounts are in place (app classes live on the mount, not in the build
# context), so vendor must be writable by the www-data runtime user.
RUN chown -R www-data:www-data vendor

COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/laravel.ini

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# production — self-contained image with code + vendor baked in
# ---------------------------------------------------------------------------
FROM base AS production

COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .

RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# nginx — serves static assets + proxies PHP to the app container.
# Rebuilt on every deploy so public/ never goes stale.
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public
