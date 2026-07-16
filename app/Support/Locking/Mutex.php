<?php

namespace App\Support\Locking;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException as LaravelLockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Go-mutex-style mutual exclusion built on Cache::lock(). The only public
 * verbs are protect() and tryProtect() so the lock is always released —
 * the equivalent of `defer mu.Unlock()` in Go.
 *
 * Pair with LockKey to remove stringly-typed keys.
 */
final class Mutex
{
    private function __construct(
        private readonly string $key,
        private readonly int $ttlSeconds,
        private readonly int $waitSeconds,
        private readonly ?string $store,
    ) {}

    public static function for(
        string $key,
        int $ttlSeconds = 30,
        int $waitSeconds = 5,
        ?string $store = null,
    ): self {
        return new self($key, $ttlSeconds, $waitSeconds, $store);
    }

    /** @throws LockTimeoutException */
    public function protect(callable $callback): mixed
    {
        $lock = $this->acquireOrThrow();

        try {
            return $callback();
        } finally {
            $this->safeRelease($lock);
        }
    }

    public function tryProtect(callable $callback): mixed
    {
        $lock = $this->makeLock();

        if (! $lock->get()) {
            return null;
        }

        try {
            return $callback();
        } finally {
            $this->safeRelease($lock);
        }
    }

    private function acquireOrThrow(): Lock
    {
        $lock = $this->makeLock();

        try {
            $lock->block($this->waitSeconds);
        } catch (LaravelLockTimeoutException) {
            throw new LockTimeoutException($this->key, $this->waitSeconds);
        }

        return $lock;
    }

    private function makeLock(): Lock
    {
        if ($this->store !== null) {
            return Cache::store($this->store)->lock($this->key, $this->ttlSeconds);
        }

        return Cache::lock($this->key, $this->ttlSeconds);
    }

    private function safeRelease(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (Throwable) {
            // Lock may have already expired via TTL — nothing to release.
        }
    }
}
