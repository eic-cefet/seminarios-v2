<?php

namespace App\Services;

use App\Jobs\RegenerateSeminarCertificatesJob;
use App\Models\Seminar;
use App\Support\Locking\LockKey;
use Illuminate\Support\Carbon;

/**
 * Debounced fan-out trigger for a seminar's issued certificates; fires
 * whenever a certificate-visible seminar field changes (see
 * SeminarCertificateObserver). Debounce semantics live in
 * RegenerationDebouncer.
 */
class SeminarCertificateRegenerationDispatcher
{
    public const COOLDOWN_MINUTES = RegenerationDebouncer::COOLDOWN_MINUTES;

    public function __construct(private readonly RegenerationDebouncer $debouncer) {}

    public function dispatchFor(Seminar $seminar): void
    {
        $this->debouncer->debounce(
            LockKey::seminarCertificateRegeneration($seminar->id),
            self::cacheKey($seminar),
            fn () => RegenerateSeminarCertificatesJob::dispatch($seminar),
            fn (Carbon $at) => RegenerateSeminarCertificatesJob::dispatch($seminar)->delay($at),
            ['seminar_id' => $seminar->id],
        );
    }

    public static function cacheKey(Seminar $seminar): string
    {
        return 'regen_certs:seminar:'.$seminar->id.':next_dispatch_at';
    }
}
