<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use App\Services\IcsGenerationService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SpeakerSeminarRescheduled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $speaker,
        public Seminar $seminar,
        public DateTimeInterface $oldScheduledAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sua apresentação foi remarcada: {$this->seminar->name} - ".config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'speaker-reschedule:'.$this->speaker->id.':'.$this->seminar->id,
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.speaker-seminar-rescheduled',
            with: [
                'speakerName' => $this->speaker->name,
                'seminar' => $this->seminar,
                'previousStartsAt' => $this->oldScheduledAt instanceof Carbon
                    ? $this->oldScheduledAt
                    : Carbon::instance($this->oldScheduledAt),
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
}
