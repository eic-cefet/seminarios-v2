<?php

namespace App\Services;

use App\Support\Locking\LockTimeoutException;
use App\Support\Locking\Mutex;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Debounces expensive fan-out dispatches so a flurry of edits to the
 * same subject collapses into at most one fan-out per cooldown window.
 *
 * Contract (identical to the original user-name debounce):
 *
 *   1. First change in a cold window — dispatch immediately and remember
 *      `now()` as the "last dispatch" time.
 *   2. Subsequent change while still inside `last_dispatch +
 *      COOLDOWN_MINUTES` — schedule one fan-out for the end of that
 *      window, then remember the target time.
 *   3. Subsequent change while a future dispatch is already pending —
 *      noop; the pending job reloads fresh state at handle() time.
 *
 * A short cache lock guards the read-decide-write sequence.
 * LockTimeoutException is swallowed deliberately: dispatchers run inside
 * model `updated` hooks and a missed debounce decision is acceptable,
 * while propagating would 500 the request that triggered the save.
 */
class RegenerationDebouncer
{
    public const COOLDOWN_MINUTES = 10;

    /**
     * @param  callable(): void  $dispatchNow
     * @param  callable(Carbon): void  $dispatchDelayed  receives the delay target
     * @param  array<string, mixed>  $logContext
     */
    public function debounce(
        string $lockKey,
        string $cacheKey,
        callable $dispatchNow,
        callable $dispatchDelayed,
        array $logContext = [],
    ): void {
        try {
            Mutex::for($lockKey, ttlSeconds: 5, waitSeconds: 2)
                ->protect(function () use ($cacheKey, $dispatchNow, $dispatchDelayed): void {
                    $nextDispatchAt = Cache::get($cacheKey);

                    if ($nextDispatchAt === null) {
                        $this->dispatchNow($dispatchNow, $cacheKey);

                        return;
                    }

                    $nextDispatchAt = Carbon::parse($nextDispatchAt);

                    if ($nextDispatchAt->isFuture()) {
                        return;
                    }

                    $cooldownEndsAt = $nextDispatchAt->copy()->addMinutes(self::COOLDOWN_MINUTES);

                    if (Carbon::now()->gte($cooldownEndsAt)) {
                        $this->dispatchNow($dispatchNow, $cacheKey);

                        return;
                    }

                    $dispatchDelayed($cooldownEndsAt);
                    Cache::put($cacheKey, $cooldownEndsAt, $cooldownEndsAt->copy()->addMinutes(5));
                });
        } catch (LockTimeoutException $e) {
            Log::warning('Regeneration dispatch lock contended; skipping debounce decision for this save.', $logContext + [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /** @param  callable(): void  $dispatchNow */
    private function dispatchNow(callable $dispatchNow, string $cacheKey): void
    {
        $dispatchNow();
        Cache::put(
            $cacheKey,
            Carbon::now(),
            Carbon::now()->addMinutes(self::COOLDOWN_MINUTES + 5),
        );
    }
}
