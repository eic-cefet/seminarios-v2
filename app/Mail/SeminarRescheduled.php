<?php

namespace App\Mail;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use App\Services\FeatureFlags;
use App\Services\IcsGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SeminarRescheduled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Seminar $seminar,
        public \DateTimeInterface $oldScheduledAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seminário reagendado: '.$this->seminar->name.' - '.config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['X-Entity-Ref-ID' => $this->refId()],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.seminar-rescheduled',
            with: [
                'userName' => $this->user->name,
                'seminar' => $this->seminar,
                'oldScheduledAt' => $this->oldScheduledAt,
                'newScheduledAt' => $this->seminar->scheduled_at,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->seminar->scheduled_at) {
            return [];
        }

        $icsContent = app(IcsGenerationService::class)->generateForSeminar($this->seminar);
        $filename = 'seminario-'.($this->seminar->slug ?? $this->seminar->id).'.ics';

        return [
            Attachment::fromData(fn () => $icsContent, $filename)
                ->withMime('text/calendar'),
        ];
    }

    public function send($mailer)
    {
        $result = parent::send($mailer);

        if (FeatureFlags::enabled('email_audit')) {
            AuditLog::record(
                AuditEvent::EmailSent,
                AuditEventType::System,
                auditable: $this->seminar,
                eventData: [
                    'mail' => self::class,
                    'to' => $this->user->email,
                    'ref_id' => $this->refId(),
                    'user_id' => $this->user->id,
                    'old_scheduled_at' => $this->oldScheduledAt->format(\DateTimeInterface::ATOM),
                ],
            );
        }

        return $result;
    }

    protected function refId(): string
    {
        return 'seminar-rescheduled:'.$this->seminar->id.':'.$this->user->id.':'.$this->oldScheduledAt->getTimestamp();
    }
}
