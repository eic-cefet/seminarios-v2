#!/bin/bash
set -e

# Simple netcat-based health check server on port 2019 (matches Easypanel health check)
(while true; do
    echo -e "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"status\":\"healthy\",\"service\":\"worker\"}" | nc -l -p 2019 -q 1
done) &

# Start the queue worker (exec makes this PID 1)
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --verbose
