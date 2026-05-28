<?php

namespace App\Services;

use App\Jobs\RegenerateUserCertificatesJob;
use App\Models\User;
use App\Support\Locking\LockKey;
use App\Support\Locking\LockTimeoutException;
use App\Support\Locking\Mutex;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Debounces certificate-regeneration fan-outs so a flurry of name
 * updates by the same user collapses into at most one fan-out per
 * cooldown window.
 *
 * Contract:
 *
 *   1. First name change in a cold window — dispatch a fan-out
 *      immediately and remember `now()` as the "last dispatch" time.
 *
 *   2. Subsequent change while still inside `last_dispatch +
 *      COOLDOWN_MINUTES` — schedule one fan-out for the end of that
 *      cooldown window, then remember the target time so further
 *      changes know there's already a pending dispatch.
 *
 *   3. Subsequent change while a future dispatch is already pending —
 *      noop. The pending fan-out's `SerializesModels` will reload the
 *      user from the database when it actually runs, so the latest
 *      edit is picked up automatically.
 *
 * A short cache lock guards the read-decide-write sequence so that two
 * concurrent name changes can't race past each other and both dispatch.
 */
class UserCertificateRegenerationDispatcher
{
    public const COOLDOWN_MINUTES = 10;

    public function dispatchFor(User $user): void
    {
        $cacheKey = self::cacheKey($user);

        // 5s lock TTL, 2s block timeout: name updates are driven by HTTP
        // requests, so contention beyond a couple of seconds means
        // something is wrong upstream — fail fast rather than hang.
        //
        // Swallowing LockTimeoutException is deliberate: this dispatcher
        // runs from User::updated() inside the save transaction, and a
        // missed debounce dispatch is acceptable (the next save will
        // pick it up) but propagating the exception would 500 the
        // user's profile update.
        try {
            Mutex::for(LockKey::userCertificateRegeneration($user->id), ttlSeconds: 5, waitSeconds: 2)
                ->protect(function () use ($user, $cacheKey): void {
                    $nextDispatchAt = Cache::get($cacheKey);

                    if ($nextDispatchAt === null) {
                        $this->dispatchNow($user, $cacheKey);

                        return;
                    }

                    $nextDispatchAt = Carbon::parse($nextDispatchAt);

                    if ($nextDispatchAt->isFuture()) {
                        // Already-scheduled fan-out will reload the user from
                        // the DB at process time and see the latest name.
                        return;
                    }

                    $cooldownEndsAt = $nextDispatchAt->copy()->addMinutes(self::COOLDOWN_MINUTES);

                    if (Carbon::now()->gte($cooldownEndsAt)) {
                        $this->dispatchNow($user, $cacheKey);

                        return;
                    }

                    RegenerateUserCertificatesJob::dispatch($user)->delay($cooldownEndsAt);
                    Cache::put($cacheKey, $cooldownEndsAt, $cooldownEndsAt->copy()->addMinutes(5));
                });
        } catch (LockTimeoutException $e) {
            Log::warning('Certificate regeneration dispatch lock contended; skipping debounce decision for this save.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public static function cacheKey(User $user): string
    {
        return 'regen_certs:user:'.$user->id.':next_dispatch_at';
    }

    private function dispatchNow(User $user, string $cacheKey): void
    {
        RegenerateUserCertificatesJob::dispatch($user);
        Cache::put(
            $cacheKey,
            Carbon::now(),
            Carbon::now()->addMinutes(self::COOLDOWN_MINUTES + 5),
        );
    }
}
