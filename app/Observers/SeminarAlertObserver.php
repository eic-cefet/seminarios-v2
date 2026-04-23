<?php

namespace App\Observers;

use App\Jobs\DispatchSeminarAlertsJob;
use App\Models\Seminar;

class SeminarAlertObserver
{
    public function created(Seminar $seminar): void
    {
        if ($seminar->active) {
            DispatchSeminarAlertsJob::dispatch($seminar)->afterCommit();
        }
    }

    public function updated(Seminar $seminar): void
    {
        if (! $seminar->wasChanged('active')) {
            return;
        }

        if ($seminar->active) {
            DispatchSeminarAlertsJob::dispatch($seminar)->afterCommit();
        }
    }
}
