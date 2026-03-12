<?php

namespace App\Services;

use App\Models\Seminar;

class IcsGenerationService
{
    public function generateForSeminar(Seminar $seminar): string
    {
        if (! $seminar->scheduled_at) {
            throw new \InvalidArgumentException('Seminar does not have a scheduled date.');
        }

        $dtStart = $seminar->scheduled_at->copy()->setTimezone('America/Sao_Paulo');
        $dtEnd = $dtStart->copy()->addHour();

        $uid = 'seminar-'.$seminar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST);
        $timestamp = now()->utc()->format('Ymd\THis\Z');
        $seminarUrl = url('/seminario/'.$seminar->slug);

        $description = strip_tags($seminar->description ?? '');
        if ($seminar->room_link) {
            $description .= ($description ? "\n\n" : '').'Link de acesso: '.$seminar->room_link;
        }

        $location = $seminar->seminarLocation?->name;

        return implode("\r\n", array_filter([
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.$this->escapeText('-//'.config('mail.name').'//Seminarios//PT'),
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$this->escapeText($uid),
            'DTSTAMP:'.$timestamp,
            'DTSTART;TZID=America/Sao_Paulo:'.$dtStart->format('Ymd\THis'),
            'DTEND;TZID=America/Sao_Paulo:'.$dtEnd->format('Ymd\THis'),
            'SUMMARY:'.$this->escapeText($seminar->name),
            $description ? 'DESCRIPTION:'.$this->escapeText($description) : null,
            $location ? 'LOCATION:'.$this->escapeText($location) : null,
            'STATUS:CONFIRMED',
            'URL:'.$this->escapeText($seminarUrl),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]));
    }

    private function escapeText(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value,
        );
    }
}
