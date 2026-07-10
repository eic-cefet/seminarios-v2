<?php

namespace App\Services;

use App\Models\User;
use App\Support\SemesterRange;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentDashboardService
{
    public function __construct(private readonly SeminarVisibilityService $visibility) {}

    public function listStudents(SemesterRange $range, User $viewer, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::whereDoesntHave('roles')
            ->whereHas('registrations', function (Builder $registrationQuery) use ($range, $viewer) {
                $registrationQuery->whereHas('seminar', function (Builder $seminarQuery) use ($range, $viewer) {
                    $seminarQuery->whereBetween('scheduled_at', [$range->startString(), $range->endString()]);
                    $this->visibility->visibleSeminars($seminarQuery, $viewer);
                });
            })
            ->with('studentData.course');

        if ($search) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * @return array{totals: array<string, int|float>, by_type: array<int, array<string, mixed>>, registrations: Collection}
     */
    public function forStudent(User $student, SemesterRange $range, User $viewer): array
    {
        $registrations = $student->registrations()
            ->whereHas('seminar', function (Builder $seminarQuery) use ($range, $viewer) {
                $seminarQuery->whereBetween('scheduled_at', [$range->startString(), $range->endString()]);
                $this->visibility->visibleSeminars($seminarQuery, $viewer);
            })
            ->with(['seminar.seminarType'])
            ->get()
            ->sortBy(fn ($registration) => $registration->seminar->scheduled_at)
            ->values();

        $attended = $registrations->filter(fn ($registration) => $registration->present);
        $missed = $registrations->filter(
            fn ($registration) => ! $registration->present && $registration->seminar->scheduled_at->isPast()
        );
        $upcoming = $registrations->filter(
            fn ($registration) => ! $registration->present && $registration->seminar->scheduled_at->isFuture()
        );

        $byType = $registrations
            ->groupBy(fn ($registration) => $registration->seminar->seminarType?->name ?? 'Não especificado')
            ->map(function (Collection $group, string $type) {
                $attendedInType = $group->filter(fn ($registration) => $registration->present);

                return [
                    'type' => $type,
                    'attended' => $attendedInType->count(),
                    'missed' => $group->filter(
                        fn ($registration) => ! $registration->present && $registration->seminar->scheduled_at->isPast()
                    )->count(),
                    'hours' => round(
                        $attendedInType->sum(fn ($registration) => (int) ($registration->seminar->duration_minutes ?? 60)) / 60,
                        2
                    ),
                ];
            })
            ->values();

        return [
            'totals' => [
                'attended' => $attended->count(),
                'missed' => $missed->count(),
                'upcoming' => $upcoming->count(),
                'hours_attended' => round(
                    $attended->sum(fn ($registration) => (int) ($registration->seminar->duration_minutes ?? 60)) / 60,
                    2
                ),
            ],
            'by_type' => $byType->all(),
            'registrations' => $registrations,
        ];
    }
}
