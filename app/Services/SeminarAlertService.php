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

        $preferences = AlertPreference::query()
            ->with('user')
            ->where('opted_in', true)
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->whereNotExists(function ($q) use ($seminar) {
                $q->select(DB::raw(1))
                    ->from('seminar_alert_dispatches')
                    ->whereColumn('seminar_alert_dispatches.user_id', 'alert_preferences.user_id')
                    ->where('seminar_alert_dispatches.seminar_id', $seminar->id);
            })
            ->get();

        return $preferences
            ->filter(fn (AlertPreference $pref) => $this->matches($pref, $seminarTypeId, $subjectIds))
            ->map(fn (AlertPreference $pref) => $pref->user)
            ->values();
    }

    /**
     * @param  array<int, int>  $seminarSubjectIds
     */
    private function matches(AlertPreference $pref, ?int $seminarTypeId, array $seminarSubjectIds): bool
    {
        $typeFilter = $pref->seminar_type_ids ?? [];
        if (! empty($typeFilter) && ! in_array($seminarTypeId, $typeFilter, true)) {
            return false;
        }

        $subjectFilter = $pref->subject_ids ?? [];
        if (! empty($subjectFilter) && empty(array_intersect($subjectFilter, $seminarSubjectIds))) {
            return false;
        }

        return true;
    }
}
