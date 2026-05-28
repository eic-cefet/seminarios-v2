<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-render an already-issued certificate's JPG + PDF artefacts at the
 * same S3 path, so any change in the printed user information (typically:
 * a corrected full name) is picked up by future downloads.
 *
 * Unlike GenerateCertificateJob this does NOT send a notification or
 * email — the download URL is unchanged, so the next fetch transparently
 * serves the refreshed file.
 */
class RegenerateCertificateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 1800;

    public function uniqueId(): string
    {
        return 'regenerate-'.(string) $this->registration->id;
    }

    public function __construct(public Registration $registration) {}

    public function handle(CertificateService $certificateService): void
    {
        $this->setAuditContext();
        $this->registration->load(['user', 'seminar.seminarType']);

        if (! $this->registration->present || empty($this->registration->certificate_code)) {
            return;
        }

        // Paths are derived from {year, seminar.slug, certificate_code} —
        // none of which depend on the user's name. Re-running the
        // generators overwrites the JPG and PDF at the exact same S3
        // keys, so existing /certificado/{code} links keep working.
        $certificateService->generateJpg($this->registration);
        $certificateService->generatePdf($this->registration);

        AuditLog::record(
            AuditEvent::CertificateRegenerated,
            AuditEventType::System,
            $this->registration,
            ['registration_id' => $this->registration->id],
        );
    }
}
