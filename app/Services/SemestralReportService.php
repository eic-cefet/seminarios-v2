<?php

namespace App\Services;

use App\Models\User;
use App\Support\SemesterRange;
use Illuminate\Support\Collection;

/**
 * Collects semestral attendance data for the admin report.
 *
 * Used by both the synchronous browser response (ReportController@semestral)
 * and the queued Excel export (GenerateSemestralReportJob). Returns one row
 * per attendee with their per-presentation breakdown and totals.
 */
final class SemestralReportService
{
    /**
     * @param  array<string,mixed>  $filters  Accepts: courses (int[]), types (int[]), situations (string[])
     * @return Collection<int, array<string,mixed>>
     */
    public function collect(string $semester, array $filters = []): Collection
    {
        $range = SemesterRange::fromString($semester);
        $startDate = $range->startString();
        $endDate = $range->endString();

        $courses = $filters['courses'] ?? [];
        $types = $filters['types'] ?? [];
        $situations = $filters['situations'] ?? [];

        $query = User::query()
            ->whereHas('registrations', function ($q) use ($startDate, $endDate, $types) {
                $q->whereHas('seminar', function ($sq) use ($startDate, $endDate, $types) {
                    $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->where('active', true);

                    if (! empty($types)) {
                        $sq->whereIn('seminar_type_id', $types);
                    }
                });
            })
            ->with([
                'studentData.course',
                'registrations' => function ($q) use ($startDate, $endDate, $types) {
                    $q->whereHas('seminar', function ($sq) use ($startDate, $endDate, $types) {
                        $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                            ->where('active', true);

                        if (! empty($types)) {
                            $sq->whereIn('seminar_type_id', $types);
                        }
                    })
                        ->with([
                            'seminar:id,name,scheduled_at,seminar_type_id,duration_minutes',
                            'seminar.seminarType:id,name',
                        ]);
                },
            ]);

        if (! empty($courses)) {
            $query->whereHas('studentData', fn ($q) => $q->whereIn('course_id', $courses));
        }

        if (! empty($situations)) {
            $query->whereHas('studentData', fn ($q) => $q->whereIn('course_situation', $situations));
        }

        return $query->get()->map(function ($user) {
            $registrations = $user->registrations;
            $totalMinutes = $registrations->sum(
                fn ($registration) => (int) ($registration->seminar->duration_minutes ?? 60)
            );

            $presentations = $registrations->map(fn ($registration) => [
                'name' => $registration->seminar->name,
                'date' => $registration->seminar->scheduled_at,
                'type' => $registration->seminar->seminarType?->name,
                'duration_minutes' => (int) ($registration->seminar->duration_minutes ?? 60),
            ])->sortBy('date')->values();

            return [
                'name' => $user->name,
                'email' => $user->email,
                'course' => $user->studentData?->course?->name ?? 'N/A',
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'presentations' => $presentations,
            ];
        })->sortBy('name')->values();
    }
}
