# syntax=docker/dockerfile:1
# Production image for Render — separate from dev so local compose keeps working

FROM php:8.4-apache AS production

# --- Extensions ---
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql opcache bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize --no-dev

# --- App source ---
COPY . .

# --- Fix permissions ---
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Apache config ---
RUN a2enmod rewrite headers

# --- Apache env port ---
ENV PORT=80

# --- Apache prefork tuned for 512 MB ---
# MaxRequestWorkers ≈ 512 / (8KB + script overhead) ≈ 40-50
# Keep conservative for free tier memory.
ENV APACHE_MAX_REQUEST_WORKERS=8

# --- User ---
USER www-data

EXPOSE 80

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV PID_FILE=/var/run/apache2.pid
ENV CACHE_DIR=/var/cache/apache2

# --- Entrypoint ---
COPY docker/render/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]