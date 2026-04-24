<?php

namespace App\Services;

use App\Models\AlertPreference;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeminarAlertService
{
    /**
     * Return the users who should receive an alert for the given seminar.
     *
     * @return Collection<int, User>
     */
    public function matchingRecipients(Seminar $seminar): Collection
    {
        $seminarTypeId = $seminar->seminar_type_id;
        $subjectIds = $seminar->subjects()->pluck('subjects.id')->all();

        return AlertPreference::query()
            ->with('user')
            ->where('new_seminar_alert', true)
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->whereNotExists(function ($q) use ($seminar) {
                $q->select(DB::raw(1))
                    ->from('seminar_alert_dispatches')
                    ->whereColumn('seminar_alert_dispatches.user_id', 'alert_preferences.user_id')
                    ->where('seminar_alert_dispatches.seminar_id', $seminar->id);
            })
            // Type filter: no type filters set, OR seminar's type is in user's list.
            ->where(function ($q) use ($seminarTypeId) {
                $q->whereDoesntHave('seminarTypes');
                if ($seminarTypeId !== null) {
                    $q->orWhereHas('seminarTypes', fn ($t) => $t->where('seminar_types.id', $seminarTypeId));
                }
            })
            // Subject filter: no subject filters set, OR shares ≥1 subject with the seminar.
            ->where(function ($q) use ($subjectIds) {
                $q->whereDoesntHave('subjects');
                if ($subjectIds !== []) {
                    $q->orWhereHas('subjects', fn ($s) => $s->whereIn('subjects.id', $subjectIds));
                }
            })
            ->get()
            ->map(fn (AlertPreference $pref) => $pref->user)
            ->filter()
            ->values();
    }
}
