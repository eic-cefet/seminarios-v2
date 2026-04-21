<?php

namespace App\Mail;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Services\FeatureFlags;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ReportReady extends Mailable
{
    use Queueable, SerializesModels;

    public string $refId;

    public function __construct(
        public string $reportName,
        public string $downloadUrl,
    ) {
        $this->refId = 'report-ready:'.(string) Str::uuid();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatório pronto: {$this->reportName}",
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['X-Entity-Ref-ID' => $this->refId],
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

    public function send($mailer)
    {
        $result = parent::send($mailer);

        if (FeatureFlags::enabled('email_audit')) {
            AuditLog::record(
                AuditEvent::EmailSent,
                AuditEventType::System,
                eventData: [
                    'mail' => self::class,
                    'ref_id' => $this->refId,
                    'report_name' => $this->reportName,
                ],
            );
        }

        return $result;
    }
}
