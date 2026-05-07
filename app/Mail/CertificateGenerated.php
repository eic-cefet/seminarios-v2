<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateGenerated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Carry only the S3 path on the mailable. The raw PDF bytes are pulled
     * lazily inside attachments(), because Laravel's queue JSON-encodes the
     * outer payload and binary content in the serialized form blows it up
     * with JSON_ERROR_UTF8 (Queue.php:130 InvalidPayloadException).
     */
    public function __construct(
        public Registration $registration,
        public string $pdfPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Certificado de Participação - '.$this->registration->seminar->name,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'certificate:'.$this->registration->id,
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.certificate-generated',
            with: [
                'userName' => $this->registration->user->name,
                'seminarName' => $this->registration->seminar->name,
                'seminarDate' => $this->registration->seminar->scheduled_at->format('d/m/Y'),
                'certificateCode' => $this->registration->certificate_code,
                'certificateUrl' => url("/certificado/{$this->registration->certificate_code}"),
            ],
        );
    }

    public function attachments(): array
    {
        $filename = 'certificado-'.$this->registration->seminar->slug.'.pdf';

        return [
            Attachment::fromData(function (): string {
                $content = Storage::disk('s3')->get($this->pdfPath);

                // Storage::get() returns null when the object is missing on
                // S3. Without this check the closure would return null,
                // produce a malformed attachment, and the SendQueuedMailable
                // job would either send a broken email or fail with an
                // opaque TypeError. Throwing here lets the queue worker
                // retry and ultimately move the mail to failed_jobs with a
                // message ops can act on.
                if ($content === null) {
                    throw new \RuntimeException(
                        "Certificate PDF not found on S3 at path: {$this->pdfPath}"
                    );
                }

                return $content;
            }, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
