<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\CommunicationCategory;
use App\Mail\CertificateGenerated;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Registration $registration,
        public bool $sendEmail = true
    ) {}

    public function handle(CertificateService $certificateService): void
    {
        $this->setAuditContext();
        $this->registration->load(['user', 'seminar.seminarType']);

        if (! $this->registration->present) {
            return;
        }

        $certificateService->ensureCertificateCode($this->registration);

        $jpgGenerated = ! $certificateService->jpgExists($this->registration);
        if ($jpgGenerated) {
            $certificateService->generateJpg($this->registration);
        }

        $pdfGenerated = ! $certificateService->pdfExists($this->registration);
        if ($pdfGenerated) {
            $certificateService->generatePdf($this->registration);
        }

        if ($this->sendEmail && ! $this->registration->certificate_sent) {
            if ($this->registration->user->wantsCommunication(CommunicationCategory::CertificateReady)) {
                $pdfPath = $certificateService->getPdfPath($this->registration);
                $pdfContent = Storage::disk('s3')->get($pdfPath);

                Mail::to($this->registration->user->email)
                    ->queue(new CertificateGenerated($this->registration, $pdfContent));
            }

            // Mark sent whether the email went out or was intentionally skipped by the
            // user's preference. Otherwise the hourly certificates:process-pending
            // scheduler re-dispatches this job every hour for opted-out users.
            $this->registration->update(['certificate_sent' => true]);
        }

        if ($jpgGenerated || $pdfGenerated) {
            AuditLog::record(AuditEvent::CertificateGenerated, AuditEventType::System, $this->registration, [
                'registration_id' => $this->registration->id,
                'jpg_generated' => $jpgGenerated,
                'pdf_generated' => $pdfGenerated,
            ]);
        }
    }
}
