<?php

namespace App\Jobs;

use App\Mail\CertificateGenerated;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Registration $registration,
        public bool $sendEmail = true
    ) {}

    public function handle(CertificateService $certificateService): void
    {
        $this->registration->load(['user', 'seminar.seminarType']);

        if (!$this->registration->present) {
            return;
        }

        $certificateService->ensureCertificateCode($this->registration);

        $jpgGenerated = false;
        $pdfGenerated = false;

        // Generate JPG if not exists
        if (!$certificateService->jpgExists($this->registration)) {
            $certificateService->generateJpg($this->registration);
            $jpgGenerated = true;
        }

        // Generate PDF if not exists
        if (!$certificateService->pdfExists($this->registration)) {
            $certificateService->generatePdf($this->registration);
            $pdfGenerated = true;
        }

        // Send email with PDF attachment if requested and not already sent
        if ($this->sendEmail && !$this->registration->certificate_sent) {
            $pdfPath = $certificateService->getPdfPath($this->registration);
            $pdfContent = Storage::disk('s3')->get($pdfPath);

            Mail::to($this->registration->user->email)
                ->send(new CertificateGenerated($this->registration, $pdfContent));

            $this->registration->certificate_sent = true;
            $this->registration->save();
        }
    }
}
