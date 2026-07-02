<?php

namespace App\Services;

use App\Jobs\RegenerateUserCertificatesJob;
use App\Models\User;
use App\Support\Locking\LockKey;
use Illuminate\Support\Carbon;

/**
 * Debounced fan-out trigger for a user's issued certificates; fires
 * whenever the user's `name` column changes (see User::booted()).
 * Debounce semantics live in RegenerationDebouncer.
 */
class UserCertificateRegenerationDispatcher
{
    public const COOLDOWN_MINUTES = RegenerationDebouncer::COOLDOWN_MINUTES;

    public function __construct(private readonly RegenerationDebouncer $debouncer) {}

    public function dispatchFor(User $user): void
    {
        $this->debouncer->debounce(
            LockKey::userCertificateRegeneration($user->id),
            self::cacheKey($user),
            fn () => RegenerateUserCertificatesJob::dispatch($user),
            fn (Carbon $at) => RegenerateUserCertificatesJob::dispatch($user)->delay($at),
            ['user_id' => $user->id],
        );
    }

    public static function cacheKey(User $user): string
    {
        return 'regen_certs:user:'.$user->id.':next_dispatch_at';
    }
}
