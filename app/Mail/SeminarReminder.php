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
        $dtEnd = $dtStart->copy()->addHour();
        $dtstamp = now()->setTimezone('America/Sao_Paulo');

        $uid = 'seminar-'.$seminar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST);

        $location = $seminar->seminarLocation?->name ?? '';
        $description = strip_tags($seminar->description ?? '');

        // Append meeting link to description if available
        if ($seminar->room_link) {
            $description .= ($description ? "\n\n" : '').'Link de acesso: '.$seminar->room_link;
        }

        $seminarUrl = url('/seminario/'.$seminar->slug);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('mail.name').'//Seminarios//PT',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VTIMEZONE',
            'TZID:America/Sao_Paulo',
            'BEGIN:STANDARD',
            'DTSTART:19700101T000000',
            'TZOFFSETFROM:-0300',
            'TZOFFSETTO:-0300',
            'TZNAME:BRT',
            'END:STANDARD',
            'END:VTIMEZONE',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            'DTSTAMP:'.$dtstamp->format('Ymd\THis'),
            'DTSTART;TZID=America/Sao_Paulo:'.$dtStart->format('Ymd\THis'),
            'DTEND;TZID=America/Sao_Paulo:'.$dtEnd->format('Ymd\THis'),
            'SUMMARY:'.$this->escapeIcsText($seminar->name),
        ];

        if ($location) {
            $lines[] = 'LOCATION:'.$this->escapeIcsText($location);
        }

        if ($description) {
            $lines[] = 'DESCRIPTION:'.$this->escapeIcsText($description);
        }

        $lines[] = 'URL:'.$this->sanitizeIcsValue($seminarUrl);
        $lines[] = 'STATUS:CONFIRMED';
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'foldIcsLine'], $lines))."\r\n";
    }

    private function escapeIcsText(string $text): string
    {
        // Escape backslashes, commas, and semicolons per RFC 5545
        $text = addcslashes($text, ',;\\');
        // Convert newlines to ICS \n encoding (also prevents CRLF injection)
        $text = preg_replace('/\r\n|\r|\n/', '\n', $text);

        return $text;
    }

    private function sanitizeIcsValue(string $value): string
    {
        return str_replace(["\r\n", "\r", "\n"], '', $value);
    }

    /**
     * Fold lines longer than 75 octets per RFC 5545 §3.1.
     */
    private function foldIcsLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = mb_substr($line, 0, 75);
        $remaining = mb_substr($line, 75);

        // Continuation lines are prefixed with a single space, max 74 chars of content
        while ($remaining !== '') {
            $folded .= "\r\n ".mb_substr($remaining, 0, 74);
            $remaining = mb_substr($remaining, 74);
        }

        return $folded;
    }
}
