<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SpeakerSeminarRecap extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $speaker,
        public Seminar $seminar,
        public int $attendeesPresent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Resumo da sua apresentação: {$this->seminar->name} - ".config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'speaker-recap:'.$this->speaker->id.':'.$this->seminar->id,
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.speaker-seminar-recap',
            with: [
                'speakerName' => $this->speaker->name,
                'seminar' => $this->seminar,
                'attendeesPresent' => $this->attendeesPresent,
            ],
        );
    }
}
