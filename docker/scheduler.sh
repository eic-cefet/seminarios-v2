#!/bin/bash
set -e

# Start health check server in background on port 2019 (matches Easypanel health check)
HEALTH_SERVICE=scheduler php -S 0.0.0.0:2019 /app/docker/health.php &

# Run the Laravel scheduler (this is PID 1)
while true; do
    php artisan schedule:run --verbose --no-interaction 2>&1 || {
        echo "Schedule:run failed with exit code $?" >&2
    }
    sleep 60
done
