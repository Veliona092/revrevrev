#!/bin/sh
set -e

# If a custom dynamic PORT is provided by Railway (and not 8080/80/3000), update config
if [ -n "$PORT" ] && [ "$PORT" != "8080" ] && [ "$PORT" != "80" ] && [ "$PORT" != "3000" ]; then
    echo "Updating Nginx for custom Railway PORT ${PORT}..."
    sed -i "s/listen 8080 default_server;/listen ${PORT} default_server;/g" /etc/nginx/nginx.conf
fi

# Ensure Nginx runtime and log directories exist
mkdir -p /run/nginx /var/log/nginx /var/lib/nginx/tmp /var/lib/nginx/logs
chown -R www-data:www-data /run/nginx /var/log/nginx /var/lib/nginx

# Ensure Laravel storage directory permissions
mkdir -p /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs
touch /var/www/html/storage/logs/laravel.log
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link || true

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application encryption key..."
    php artisan key:generate --force || true
fi

# Run database migrations
if [ -n "$DB_HOST" ] || [ -n "$MYSQLHOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Database migration skipped or connection pending."
fi

# Cache config, routes, views
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Validate Nginx configuration
echo "Testing Nginx configuration..."
nginx -t

echo "Starting Supervisor (Nginx + PHP-FPM)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf