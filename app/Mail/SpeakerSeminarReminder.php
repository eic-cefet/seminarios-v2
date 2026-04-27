<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use App\Services\IcsGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SpeakerSeminarReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $speaker,
        public Seminar $seminar,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Você apresenta amanhã: {$this->seminar->name} - ".config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'speaker-reminder-24h:'.$this->speaker->id.':'.$this->seminar->id.':'.now()->format('Ymd'),
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.speaker-seminar-reminder',
            with: [
                'speakerName' => $this->speaker->name,
                'seminar' => $this->seminar,
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

        $ics = app(IcsGenerationService::class)->generateForSeminar($this->seminar);
        $filename = 'seminario-'.($this->seminar->slug ?? $this->seminar->id).'.ics';

        return [
            Attachment::fromData(fn () => $ics, $filename)->withMime('text/calendar'),
        ];
    }
}
