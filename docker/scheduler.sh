#!/bin/bash
set -e

# Simple netcat-based health check server on port 2019 (matches Easypanel health check)
# Responds to any HTTP request with 200 OK
(while true; do
    echo -e "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"status\":\"healthy\",\"service\":\"scheduler\"}" | nc -l -p 2019 -q 1
done) &

# Run the Laravel scheduler (this is PID 1)
while true; do
    php artisan schedule:run --verbose --no-interaction 2>&1 || {
        echo "Schedule:run failed with exit code $?" >&2
    }
    sleep 60
done
