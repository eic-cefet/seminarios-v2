<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportName,
        public string $downloadUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatório pronto: {$this->reportName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.report-ready',
            with: [
                'reportName' => $this->reportName,
                'downloadUrl' => $this->downloadUrl,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
