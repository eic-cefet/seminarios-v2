<?php

namespace App\Support\External;

use Illuminate\Support\Facades\Cache;

class IdempotencyStore
{
    private const TTL_SECONDS = 86400;

    /** @return array{request_hash: string, status: int, body: string, headers: array<string, string>}|null */
    public function get(string $tokenScope, string $key): ?array
    {
        $value = Cache::get($this->cacheKey($tokenScope, $key));

        return is_array($value) ? $value : null;
    }

    /** @param array{request_hash: string, status: int, body: string, headers: array<string, string>} $value */
    public function put(string $tokenScope, string $key, array $value): void
    {
        Cache::put($this->cacheKey($tokenScope, $key), $value, self::TTL_SECONDS);
    }

    private function cacheKey(string $tokenScope, string $key): string
    {
        return 'external_api:idempotency:'.$tokenScope.':'.hash('sha256', $key);
    }
}
