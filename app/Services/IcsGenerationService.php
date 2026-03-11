<?php

namespace App\Services;

use App\Models\Seminar;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;

class IcsGenerationService
{
    public function generateForSeminar(Seminar $seminar): string
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
