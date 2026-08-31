#!/bin/sh
set -e

PORT_NUM="${PORT:-8080}"
sed -i "s/__PORT__/${PORT_NUM}/g" /etc/nginx/nginx.conf

echo "Starting Laravel initialization on port ${PORT_NUM}..."

# Ensure storage directory permissions
mkdir -p /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link || true

# Run database migrations if DB is configured
if [ -n "$DB_HOST" ] || [ -n "$MYSQLHOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration skipped or failed."
fi

# Optimize Laravel cache
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Nginx and PHP-FPM via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf