#!/bin/bash
set -e

cd /var/www/sites/eic-seminarios.com

# Create .env file from environment variables if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env from environment variables..."
    cp .env.example .env 2>/dev/null || touch .env
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
while ! php -r "try { new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306}', '${DB_USERNAME:-seminarios}', '${DB_PASSWORD:-seminarios}'); echo 'ok'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    sleep 2
done
echo "MySQL is ready!"

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache routes and views for production
php artisan route:cache
php artisan view:cache

# Set correct permissions
chown -R www-data:www-data storage bootstrap/cache

echo "Starting Apache..."
exec apache2ctl -D FOREGROUND
