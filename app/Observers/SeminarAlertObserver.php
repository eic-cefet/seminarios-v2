<?php

namespace App\Observers;

use App\Jobs\DispatchSeminarAlertsJob;
use App\Models\AlertPreference;
use App\Models\Seminar;

class SeminarAlertObserver
{
    public function created(Seminar $seminar): void
    {
        $this->maybeDispatch($seminar);
    }

    public function updated(Seminar $seminar): void
    {
        if (! $seminar->wasChanged('active')) {
            return;
        }

        $this->maybeDispatch($seminar);
    }

    private function maybeDispatch(Seminar $seminar): void
    {
        if (! $seminar->active) {
            return;
        }

        if (! AlertPreference::query()->where('new_seminar_alert', true)->exists()) {
            return;
        }

        DispatchSeminarAlertsJob::dispatch($seminar)->afterCommit();
    }
}
