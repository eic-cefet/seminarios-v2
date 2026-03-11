<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

class SeminarRescheduled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Seminar $seminar,
        public Carbon $oldScheduledAt,
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
        if (! $this->seminar->scheduled_at) {
            return [];
        }

        $icsContent = $this->generateIcs($this->seminar);
        $filename = 'seminario-'.($this->seminar->slug ?? $this->seminar->id).'.ics';

        return [
            Attachment::fromData(fn () => $icsContent, $filename)
                ->withMime('text/calendar'),
        ];
    }

    private function generateIcs(Seminar $seminar): string
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
