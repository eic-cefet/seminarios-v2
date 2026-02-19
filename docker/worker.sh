#!/bin/bash
set -e

# Start health check server in background on port 2019 (matches Easypanel health check)
HEALTH_SERVICE=worker php -q -S 0.0.0.0:2019 /app/docker/health.php &

# Run the queue worker in a loop so the container stays alive.
# --max-time=3600 causes a graceful exit after 1 hour to free memory;
# the loop restarts it automatically within the same container.
while true; do
    php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --verbose 2>&1 || {
        echo "Queue worker exited with code $?, restarting in 5s..." >&2
        sleep 5
    }
    echo "Queue worker reached max-time, restarting..."
done
