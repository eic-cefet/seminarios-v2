#!/bin/bash
set -e

# Run migrations if AUTO_MIGRATE is set
if [ "$AUTO_MIGRATE" = "true" ]; then
    php artisan migrate --force
fi

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start Octane with FrankenPHP (HTTPS with auto-generated certs)
exec php artisan octane:frankenphp --host=0.0.0.0 --port=443 --https
