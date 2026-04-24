<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestMiddleware
{
    /** @var array<string> */
    protected array $sensitiveHeaders = [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /** @var array<string> */
    private const REDACT_KEYS = ['email', 'password', 'password_confirmation', 'token', '_token', 'api_token'];

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_log_start_time', microtime(true));

        $response = $next($request);

        $request->attributes->set('_log_response_status', $response->getStatusCode());
        if ($response->headers->has('Content-Length')) {
            $request->attributes->set('_log_response_size', (int) $response->headers->get('Content-Length'));
        }

        return $response;
    }

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
            'full_url' => $this->redactedUrl($request),
            'query_params' => $this->redactedQuery($request),
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
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function redact(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACT_KEYS, true)) {
                $out[$key] = '[redacted]';

                continue;
            }
            $out[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $out;
    }

    /** @return array<string, mixed>|null */
    private function redactedQuery(Request $request): ?array
    {
        $query = $request->query();
        if (empty($query)) {
            return null;
        }

        return $this->redact($query);
    }

    private function redactedUrl(Request $request): string
    {
        $base = $request->url();
        $query = $this->redactedQuery($request);
        if (empty($query)) {
            return $base;
        }

        return $base.'?'.http_build_query($query);
    }

    /**
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
