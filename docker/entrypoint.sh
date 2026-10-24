#!/bin/bash
set -e

cd /var/www/html

# Render injects PORT (default 10000); point nginx at it.
sed -i "s/listen 10000 default_server/listen ${PORT:-10000} default_server/" /etc/nginx/http.d/default.conf
sed -i "s/listen \[::\]:10000 default_server/listen [::]:${PORT:-10000} default_server/" /etc/nginx/http.d/default.conf

# Fresh environment on every boot: regenerate cached config from env vars.
php artisan config:clear
php artisan config:cache
php artisan route:cache

# Ensure writable dirs survive image rebuilds.
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Apply pending migrations (Neon needs the pooled URL for this).
php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisord.conf