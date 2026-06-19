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
use Illuminate\Support\Facades\Log;
use Throwable;

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

        Log::info('RegenerateCertificateJob: starting', [
            'registration_id' => $this->registration->id,
            'seminar_id' => $this->registration->seminar_id,
            'seminar_type' => $this->registration->seminar?->seminarType?->name,
            'certificate_code' => $this->registration->certificate_code,
        ]);

        if (! $this->registration->present || empty($this->registration->certificate_code)) {
            Log::warning('RegenerateCertificateJob: skipped — registration not present or missing certificate code', [
                'registration_id' => $this->registration->id,
                'present' => (bool) $this->registration->present,
                'has_certificate_code' => ! empty($this->registration->certificate_code),
            ]);

            return;
        }

        // Paths are derived from {year, seminar.slug, certificate_code} —
        // none of which depend on the user's name. Re-running the
        // generators overwrites the JPG and PDF at the exact same S3
        // keys, so existing /certificado/{code} links keep working.
        $jpgPath = $certificateService->generateJpg($this->registration);
        $pdfPath = $certificateService->generatePdf($this->registration);

        Log::info('RegenerateCertificateJob: regenerated certificate artefacts', [
            'registration_id' => $this->registration->id,
            'seminar_id' => $this->registration->seminar_id,
            'seminar_type' => $this->registration->seminar?->seminarType?->name,
            'jpg_path' => $jpgPath,
            'pdf_path' => $pdfPath,
        ]);

        AuditLog::record(
            AuditEvent::CertificateRegenerated,
            AuditEventType::System,
            $this->registration,
            ['registration_id' => $this->registration->id],
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('RegenerateCertificateJob: failed permanently after retries', [
            'registration_id' => $this->registration->id,
            'seminar_id' => $this->registration->seminar_id,
            'certificate_code' => $this->registration->certificate_code,
            'exception' => $exception->getMessage(),
        ]);
    }
}
