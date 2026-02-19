#!/bin/bash
set -e

# Graceful shutdown: stop the loop after the current schedule:run finishes
SHUTDOWN=false
trap 'SHUTDOWN=true; echo "Received shutdown signal, waiting for current schedule run to finish..."' SIGTERM SIGINT

# Start health check server in background on port 2019 (matches Easypanel health check)
HEALTH_SERVICE=scheduler php -q -S 0.0.0.0:2019 /app/docker/health.php &

# Run the Laravel scheduler
while [ "$SHUTDOWN" = false ]; do
    php artisan schedule:run --verbose --no-interaction 2>&1 || {
        echo "Schedule:run failed with exit code $?" >&2
    }
    sleep 60
done
