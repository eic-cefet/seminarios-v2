<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public string $pdfContent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Certificado de Participação - ' . $this->registration->seminar->name,
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
        $filename = 'certificado-' . $this->registration->seminar->slug . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
