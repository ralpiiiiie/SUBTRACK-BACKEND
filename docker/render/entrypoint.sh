#!/bin/sh
set -e

# --- Listen on Render's port ---
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# --- Run database migrations if requested ---
if [ "$RUN_MIGRATIONS" = "true" ]; then
    DB_URL="${DB_URL_DIRECT:-$DB_URL}" php artisan migrate --force
fi

# --- Cache config, routes, and events (after env vars are set) ---
php artisan config:cache
php artisan route:cache
php artisan event:cache

# --- Start Apache ---
exec apache2-foreground