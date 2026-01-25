<?php

/**
 * Simple health check endpoint for worker/scheduler containers.
 * Returns 200 OK with JSON status.
 */

header('Content-Type: application/json');
http_response_code(200);

echo json_encode([
    'status' => 'healthy',
    'service' => $_ENV['HEALTH_SERVICE'] ?? 'unknown',
    'timestamp' => date('c'),
]);
