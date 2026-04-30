<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Support\External\IdempotencyStore;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdempotency
{
    private const LOCK_TTL_SECONDS = 10;

    private const LOCK_BLOCK_SECONDS = 5;

    public function __construct(private readonly IdempotencyStore $store) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (! is_string($key) || $key === '') {
            return $next($request);
        }

        if (strlen($key) > 200 || ! preg_match('/^[A-Za-z0-9._:\-]+$/', $key)) {
            throw ApiException::validation(['Idempotency-Key' => 'Invalid format']);
        }

        $tokenScope = (string) ($request->user()?->currentAccessToken()?->id ?? 'session');
        $hash = hash('sha256', $request->getContent() ?: '');

        $lock = Cache::lock(IdempotencyStore::lockKey($tokenScope, $key), self::LOCK_TTL_SECONDS);

        try {
            $lock->block(self::LOCK_BLOCK_SECONDS);
        } catch (LockTimeoutException) {
            throw ApiException::idempotencyKeyConflict();
        }

        try {
            $existing = $this->store->get($tokenScope, $key);
            if ($existing !== null) {
                if ($existing['request_hash'] !== $hash) {
                    throw ApiException::idempotencyKeyConflict();
                }
                $resp = response($existing['body'], $existing['status']);
                foreach ($existing['headers'] as $h => $v) {
                    $resp->headers->set($h, $v);
                }
                $resp->headers->set('Idempotent-Replayed', 'true');

                return $resp;
            }

            /** @var Response $response */
            $response = $next($request);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $this->store->put($tokenScope, $key, [
                    'request_hash' => $hash,
                    'status' => $response->getStatusCode(),
                    'body' => (string) $response->getContent(),
                    'headers' => ['Content-Type' => $response->headers->get('Content-Type', 'application/json')],
                ]);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
