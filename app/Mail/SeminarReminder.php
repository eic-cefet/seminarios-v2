<?php

namespace App\Mail;

use App\Models\User;
use App\Services\IcsGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SeminarReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Seminar>  $seminars
     */
    public function __construct(
        public User $user,
        public Collection $seminars,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->seminars->count();
        $subject = $count === 1
            ? 'Lembrete: Seminário amanhã!'
            : "Lembrete: {$count} seminários amanhã!";

        return new Envelope(
            subject: $subject.' - '.config('mail.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.seminar-reminder',
            with: [
                'userName' => $this->user->name,
                'seminars' => $this->seminars,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $icsService = app(IcsGenerationService::class);
        $attachments = [];

        foreach ($this->seminars as $seminar) {
            if ($seminar->scheduled_at) {
                $icsContent = $icsService->generateForSeminar($seminar);
                $filename = 'seminario-'.($seminar->slug ?? $seminar->id).'.ics';

                $attachments[] = Attachment::fromData(fn () => $icsContent, $filename)
                    ->withMime('text/calendar');
            }
        }

        return $attachments;
    }
}
