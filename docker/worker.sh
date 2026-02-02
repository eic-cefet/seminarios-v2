#!/bin/bash
set -e

# Start health check server in background on port 2019 (matches Easypanel health check)
HEALTH_SERVICE=worker php -q -S 0.0.0.0:2019 /app/docker/health.php &

# Start the queue worker (exec makes this PID 1)
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --verbose
