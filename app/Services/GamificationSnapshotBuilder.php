<?php

namespace App\Services;

use App\Enums\ExperienceReason;
use App\Gamification\GamificationSnapshot;
use App\Models\User;
use App\Models\Workshop;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class GamificationSnapshotBuilder
{
    public function for(User $user): GamificationSnapshot
    {
        $registrations = $user->registrations()
            ->select(['id', 'seminar_id'])
            ->where('present', true)
            ->whereHas('seminar')
            ->with([
                'seminar:id,workshop_id,seminar_type_id,scheduled_at,active',
                'seminar.subjects:id',
                'seminar.speakers:id',
            ])
            ->orderBy('id')
            ->get();

        $attendedSeminarIds = $registrations->pluck('seminar_id')->unique()->values();
        $ratings = $user->ratings()
            ->select(['id', 'seminar_id'])
            ->whereIn('seminar_id', $attendedSeminarIds)
            ->orderBy('id')
            ->get();

        $subjects = $registrations
            ->flatMap(fn ($registration) => $registration->seminar->subjects)
            ->unique('id')
            ->sortBy('id')
            ->values();
        $seminarTypes = $registrations
            ->pluck('seminar.seminar_type_id')
            ->filter()
            ->unique()
            ->values();
        $speakers = $registrations
            ->flatMap(fn ($registration) => $registration->seminar->speakers)
            ->unique('id')
            ->values();
        $dates = $registrations
            ->map(fn ($registration) => $registration->seminar->scheduled_at?->setTimezone(config('app.timezone')))
            ->filter()
            ->values();
        $completedWorkshops = $this->completedWorkshops(
            $registrations->pluck('seminar.workshop_id')->filter()->unique()->values(),
            $attendedSeminarIds,
        );

        $metrics = [
            'attendance_count' => $registrations->count(),
            'distinct_subjects' => $subjects->count(),
            'distinct_types' => $seminarTypes->count(),
            'distinct_speakers' => $speakers->count(),
            'max_attendances_day' => $this->maximumFor($dates, fn (CarbonInterface $date): string => $date->format('Y-m-d')),
            'max_attendances_week' => $this->maximumFor($dates, fn (CarbonInterface $date): string => $date->isoWeekYear().'-'.$date->isoWeek()),
            'max_attendances_month' => $this->maximumFor($dates, fn (CarbonInterface $date): string => $date->format('Y-m')),
            'max_attendances_semester' => $this->maximumFor($dates, fn (CarbonInterface $date): string => $date->year.'.'.($date->month <= 6 ? 1 : 2)),
            'max_attendances_year' => $this->maximumFor($dates, fn (CarbonInterface $date): string => (string) $date->year),
            'active_semesters' => $dates
                ->map(fn (CarbonInterface $date): string => $date->year.'.'.($date->month <= 6 ? 1 : 2))
                ->unique()
                ->count(),
            'completed_workshops' => $completedWorkshops->count(),
            'evaluation_count' => $ratings->count(),
        ];

        return GamificationSnapshot::fromMetrics($metrics, $this->experienceSources(
            $registrations,
            $ratings,
            $subjects,
            $completedWorkshops,
        ));
    }

    /**
     * @param  Collection<int, int>  $candidateWorkshopIds
     * @param  Collection<int, int>  $attendedSeminarIds
     * @return Collection<int, Workshop>
     */
    private function completedWorkshops(Collection $candidateWorkshopIds, Collection $attendedSeminarIds): Collection
    {
        if ($candidateWorkshopIds->isEmpty()) {
            return collect();
        }

        return Workshop::query()
            ->whereIn('id', $candidateWorkshopIds)
            ->with(['seminars' => function (HasMany $query): void {
                $query->select(['id', 'workshop_id', 'scheduled_at'])
                    ->active()
                    ->orderBy('id');
            }])
            ->orderBy('id')
            ->get()
            ->filter(function (Workshop $workshop) use ($attendedSeminarIds): bool {
                $activeSeminars = $workshop->seminars;

                return $activeSeminars->count() >= 2
                    && $activeSeminars->every(
                        fn ($seminar): bool => ! $seminar->scheduled_at->setTimezone(config('app.timezone'))->isFuture(),
                    )
                    && $activeSeminars->pluck('id')->diff($attendedSeminarIds)->isEmpty();
            })
            ->values();
    }

    /**
     * @param  Collection<int, CarbonInterface>  $dates
     */
    private function maximumFor(Collection $dates, callable $key): int
    {
        return (int) ($dates->countBy($key)->max() ?? 0);
    }

    /**
     * @param  Collection<int, mixed>  $registrations
     * @param  Collection<int, mixed>  $ratings
     * @param  Collection<int, mixed>  $subjects
     * @param  Collection<int, Workshop>  $completedWorkshops
     * @return array<array{reason: ExperienceReason, source_key: string, points: int}>
     */
    private function experienceSources(
        Collection $registrations,
        Collection $ratings,
        Collection $subjects,
        Collection $completedWorkshops,
    ): array {
        $sources = [];

        foreach ($registrations as $registration) {
            $this->addSource(
                $sources,
                ExperienceReason::Attendance,
                "attendance:{$registration->id}",
                config('gamification.points.attendance'),
            );
        }

        foreach ($ratings as $rating) {
            $this->addSource(
                $sources,
                ExperienceReason::Evaluation,
                "evaluation:{$rating->id}",
                config('gamification.points.evaluation'),
            );
        }

        foreach ($subjects as $subject) {
            $this->addSource(
                $sources,
                ExperienceReason::NewSubject,
                "subject:{$subject->id}",
                config('gamification.points.new_subject'),
            );
        }

        foreach ($completedWorkshops as $workshop) {
            $this->addSource(
                $sources,
                ExperienceReason::WorkshopCompletion,
                "workshop:{$workshop->id}",
                config('gamification.points.workshop_completion'),
            );
        }

        ksort($sources);

        return array_values($sources);
    }

    /**
     * @param  array<string, array{reason: ExperienceReason, source_key: string, points: int}>  $sources
     */
    private function addSource(
        array &$sources,
        ExperienceReason $reason,
        string $sourceKey,
        int $points,
    ): void {
        $sources["{$reason->value}|{$sourceKey}"] = [
            'reason' => $reason,
            'source_key' => $sourceKey,
            'points' => $points,
        ];
    }
}
