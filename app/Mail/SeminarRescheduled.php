<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
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
            subject: 'Apresentação reagendada: '.$this->seminar->name.' - '.config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => $this->refId(),
                'X-Mail-Class' => self::class,
            ],
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

    protected function refId(): string
    {
        return 'seminar-rescheduled:'.$this->seminar->id.':'.$this->user->id.':'.$this->oldScheduledAt->getTimestamp();
    }
}
