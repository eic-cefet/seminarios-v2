<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

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
            subject: 'Alteração de data: '.$this->seminar->name.' - '.config('mail.name'),
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
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $icsContent = $this->generateIcs($this->seminar);
        $filename = 'seminario-'.($this->seminar->slug ?? $this->seminar->id).'.ics';

        return [
            Attachment::fromData(fn () => $icsContent, $filename)
                ->withMime('text/calendar'),
        ];
    }

    private function generateIcs(Seminar $seminar): string
    {
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $dtStart = $seminar->scheduled_at->copy()->setTimezone($tz);
        $dtEnd = $dtStart->copy()->addHour();

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'app';
        $uid = 'seminar-'.$seminar->id.'@'.$host;

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
