#!/bin/bash
set -e

# Start health check server in background
HEALTH_SERVICE=scheduler php -S 0.0.0.0:8000 /app/docker/health.php &

# Run the Laravel scheduler
while true; do
    php artisan schedule:run --verbose --no-interaction
    sleep 60
done
