#!/bin/bash
set -e

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start Octane with FrankenPHP
# TLS should be handled by reverse proxy/load balancer in production
exec php artisan octane:frankenphp --host=0.0.0.0 --port=8000
