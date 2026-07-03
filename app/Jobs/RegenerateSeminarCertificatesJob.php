<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fan out one RegenerateCertificateJob per already-issued certificate of
 * the given seminar. Dispatched (immediately or with a delay) by
 * SeminarCertificateRegenerationDispatcher whenever a field that is baked
 * into the rendered certificate (name, scheduled_at, seminar_type_id)
 * changes, so previously-issued certificates pick up the correction.
 *
 * Intentionally NOT ShouldBeUnique — debouncing happens in front of
 * dispatch, and SerializesModels reloads the seminar at handle() time so
 * a pending fan-out always renders the latest state.
 */
class RegenerateSeminarCertificatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Seminar $seminar) {}

    public function handle(): void
    {
        $this->setAuditContext();

        $this->seminar->registrations()
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->each(function ($registration): void {
                RegenerateCertificateJob::dispatch($registration);
            });
    }
}
