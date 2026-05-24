<?php

namespace App\Support;

use App\Models\Seminar;
use Illuminate\Support\Carbon;

final class PresenceLinkPolicy
{
    public static function expiresAt(Seminar $seminar): Carbon
    {
        $endsAfterFourHours = Carbon::parse($seminar->scheduled_at)->addHours(4);
        $minimumOneHourWindow = Carbon::now()->addHour();

        return $endsAfterFourHours->greaterThan($minimumOneHourWindow)
            ? $endsAfterFourHours
            : $minimumOneHourWindow;
    }

    public static function defaultActive(): bool
    {
        return true;
    }
}
