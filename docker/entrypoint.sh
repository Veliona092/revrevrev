#!/bin/sh
set -e

PORT_NUM="${PORT:-8080}"
echo "Configuring Nginx for port ${PORT_NUM}..."
sed -i "s/__PORT__/${PORT_NUM}/g" /etc/nginx/nginx.conf

# Ensure Nginx run directories exist
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
    echo "Generating new application key..."
    php artisan key:generate --force || true
fi

# Run database migrations
if [ -n "$DB_HOST" ] || [ -n "$MYSQLHOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Database migration failed or database not reachable yet."
fi

# Cache configuration, routes, and views
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Ready! Starting Nginx and PHP-FPM on port ${PORT_NUM}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf