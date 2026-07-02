<?php

namespace App\Observers;

use App\Models\Seminar;
use App\Services\SeminarCertificateRegenerationDispatcher;

/**
 * Keeps already-issued certificates truthful: whenever a field that is
 * rendered into the certificate artwork changes, fan out a debounced
 * regeneration for every issued certificate of the seminar.
 */
class SeminarCertificateObserver
{
    /** Fields baked into the rendered certificate (CertificateService::generateJpg). */
    public const WATCHED_FIELDS = ['name', 'scheduled_at', 'seminar_type_id'];

    public function __construct(private readonly SeminarCertificateRegenerationDispatcher $dispatcher) {}

    public function updated(Seminar $seminar): void
    {
        if (! $seminar->wasChanged(self::WATCHED_FIELDS)) {
            return;
        }

        $hasIssuedCertificates = $seminar->registrations()
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->exists();

        if (! $hasIssuedCertificates) {
            return;
        }

        $this->dispatcher->dispatchFor($seminar);
    }
}
