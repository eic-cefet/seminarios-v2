<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSemestralReportJob;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function courses(): JsonResponse
    {
        $courses = Course::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $courses->map(fn ($course) => [
                'value' => $course->id,
                'label' => $course->name,
            ]),
        ]);
    }

    public function semestral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['required', 'string', 'regex:/^\d{4}\.[12]$/'],
            'courses' => 'nullable|array',
            'courses.*' => 'integer',
            'types' => 'nullable|array',
            'types.*' => 'integer',
            'situations' => 'nullable|array',
            'situations.*' => 'string',
            'format' => 'required|in:browser,excel',
        ]);

        if ($validated['format'] === 'excel') {
            GenerateSemestralReportJob::dispatch(
                $request->user(),
                $validated['semester'],
                $validated['courses'] ?? [],
                $validated['types'] ?? [],
                $validated['situations'] ?? [],
            );

            AuditLog::record(AuditEvent::ReportQueued, eventData: [
                'type' => 'semestral',
                'semester' => $validated['semester'],
                'format' => 'excel',
                'filters' => array_filter([
                    'courses' => $validated['courses'] ?? null,
                    'types' => $validated['types'] ?? null,
                    'situations' => $validated['situations'] ?? null,
                ]),
            ]);

            return response()->json([
                'message' => 'Relatório sendo gerado. Você receberá um e-mail em breve.',
            ]);
        }

        [$year, $semester] = explode('.', $validated['semester']);

        if ($semester == 1) {
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-06-30 23:59:59";
        } else {
            $startDate = "{$year}-07-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";
        }

        $query = User::query()
            ->whereHas('registrations', function ($q) use ($startDate, $endDate, $validated) {
                $q->whereHas('seminar', function ($sq) use ($startDate, $endDate, $validated) {
                    $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->where('active', true);

                    if (! empty($validated['types'])) {
                        $sq->whereIn('seminar_type_id', $validated['types']);
                    }
                });
            })
            ->with([
                'studentData.course',
                'registrations' => function ($q) use ($startDate, $endDate, $validated) {
                    $q->whereHas('seminar', function ($sq) use ($startDate, $endDate, $validated) {
                        $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                            ->where('active', true);

                        if (! empty($validated['types'])) {
                            $sq->whereIn('seminar_type_id', $validated['types']);
                        }
                    })
                        ->with([
                            'seminar:id,name,scheduled_at,seminar_type_id,duration_minutes',
                            'seminar.seminarType:id,name',
                        ]);
                },
            ]);

        if (! empty($validated['courses'])) {
            $query->whereHas('studentData', function ($q) use ($validated) {
                $q->whereIn('course_id', $validated['courses']);
            });
        }

        if (! empty($validated['situations'])) {
            $query->whereHas('studentData', function ($q) use ($validated) {
                $q->whereIn('course_situation', $validated['situations']);
            });
        }

        $reportData = $query->get()->map(function ($user) {
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

        AuditLog::record(AuditEvent::ReportGenerated, eventData: [
            'semester' => $validated['semester'],
            'format' => $validated['format'],
            'filters' => array_filter([
                'courses' => $validated['courses'] ?? null,
                'types' => $validated['types'] ?? null,
                'situations' => $validated['situations'] ?? null,
            ]),
        ]);

        return response()->json([
            'data' => $reportData,
            'summary' => [
                'total_users' => $reportData->count(),
                'total_hours' => round($reportData->sum('total_minutes') / 60, 2),
                'semester' => $validated['semester'],
            ],
        ]);
    }
}
