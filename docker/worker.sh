#!/bin/bash
set -e

# Start health check server in background
HEALTH_SERVICE=worker php -S 0.0.0.0:8000 /app/docker/health.php &

# Start the queue worker
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
