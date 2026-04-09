<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestMiddleware
{
    /**
     * Sensitive headers that should not be logged.
     *
     * @var array<string>
     */
    protected array $sensitiveHeaders = [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_log_start_time', microtime(true));

        $response = $next($request);

        // Store response data for terminate()
        $request->attributes->set('_log_response_status', $response->getStatusCode());
        if ($response->headers->has('Content-Length')) {
            $request->attributes->set('_log_response_size', (int) $response->headers->get('Content-Length'));
        }

        return $response;
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! config('logging.enable_request_logging', true)) {
            return;
        }

        $startTime = $request->attributes->get('_log_start_time');
        if ($startTime === null) {
            return;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'query_params' => $request->query() ?: null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'request_size' => (int) $request->header('Content-Length', 0),
            'response_status' => $request->attributes->get('_log_response_status'),
            'response_size' => $request->attributes->get('_log_response_size'),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
            'headers' => $this->filterHeaders($request->headers->all()),
        ];

        $message = sprintf(
            '%s %s - %d (%sms)',
            $request->method(),
            $request->path(),
            $context['response_status'],
            $duration
        );

        Log::info($message, $context);
    }

    /**
     * Filter out sensitive headers from the log.
     *
     * @param  array<string, array<string>>  $headers
     * @return array<string, array<string>>
     */
    protected function filterHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            fn ($key) => ! in_array(strtolower($key), $this->sensitiveHeaders),
            ARRAY_FILTER_USE_KEY
        );
    }
}
