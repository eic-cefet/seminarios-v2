<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

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
        $attachments = [];

        foreach ($this->seminars as $seminar) {
            if ($seminar->scheduled_at) {
                $icsContent = $this->generateIcs($seminar);
                $filename = 'seminario-'.($seminar->slug ?? $seminar->id).'.ics';

                $attachments[] = Attachment::fromData(fn () => $icsContent, $filename)
                    ->withMime('text/calendar');
            }
        }

        return $attachments;
    }

    private function generateIcs(\App\Models\Seminar $seminar): string
    {
        $dtStart = $seminar->scheduled_at->setTimezone('America/Sao_Paulo');
        $dtEnd = $dtStart->copy()->addHour();

        $uid = 'seminar-'.$seminar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST);

        $description = strip_tags($seminar->description ?? '');
        if ($seminar->room_link) {
            $description .= ($description ? "\n\n" : '').'Link de acesso: '.$seminar->room_link;
        }

        $event = Event::create($seminar->name)
            ->uniqueIdentifier($uid)
            ->startsAt($dtStart)
            ->endsAt($dtEnd)
            ->status(EventStatus::Confirmed)
            ->url(url('/seminario/'.$seminar->slug));

        $location = $seminar->seminarLocation?->name;
        if ($location) {
            $event->address($location);
        }

        if ($description) {
            $event->description($description);
        }

        return Calendar::create()
            ->productIdentifier('-//'.config('mail.name').'//Seminarios//PT')
            ->event($event)
            ->get();
    }
}
