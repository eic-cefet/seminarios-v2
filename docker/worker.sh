#!/bin/bash
set -e

# Start the queue worker
exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
