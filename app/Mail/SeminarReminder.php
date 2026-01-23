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
        $dtEnd = $dtStart->copy()->addHours(2);
        $dtstamp = now()->setTimezone('America/Sao_Paulo');

        $uid = 'seminar-'.$seminar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST);

        $location = $seminar->seminarLocation?->name ?? '';
        $description = strip_tags($seminar->description ?? '');

        // Append meeting link to description if available
        if ($seminar->room_link) {
            $description .= ($description ? "\n\n" : '').'Link de acesso: '.$seminar->room_link;
        }

        $description = str_replace(["\r\n", "\n", "\r"], '\n', $description);

        $seminarUrl = url('/seminario/'.$seminar->slug);

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= 'PRODID:-//'.config('mail.name')."//Seminarios//PT\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VTIMEZONE\r\n";
        $ics .= "TZID:America/Sao_Paulo\r\n";
        $ics .= "BEGIN:STANDARD\r\n";
        $ics .= "DTSTART:19700101T000000\r\n";
        $ics .= "TZOFFSETFROM:-0300\r\n";
        $ics .= "TZOFFSETTO:-0300\r\n";
        $ics .= "TZNAME:BRT\r\n";
        $ics .= "END:STANDARD\r\n";
        $ics .= "END:VTIMEZONE\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= 'DTSTAMP:'.$dtstamp->format('Ymd\THis')."\r\n";
        $ics .= 'DTSTART;TZID=America/Sao_Paulo:'.$dtStart->format('Ymd\THis')."\r\n";
        $ics .= 'DTEND;TZID=America/Sao_Paulo:'.$dtEnd->format('Ymd\THis')."\r\n";
        $ics .= 'SUMMARY:'.$this->escapeIcsText($seminar->name)."\r\n";

        if ($location) {
            $ics .= 'LOCATION:'.$this->escapeIcsText($location)."\r\n";
        }

        if ($description) {
            $ics .= 'DESCRIPTION:'.$this->escapeIcsText($description)."\r\n";
        }

        $ics .= 'URL:'.$seminarUrl."\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function escapeIcsText(string $text): string
    {
        return preg_replace('/[\r\n]+/', '\n', addcslashes($text, ',;\\'));
    }
}
