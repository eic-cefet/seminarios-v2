<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SemestralReportExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function courses()
    {
        $courses = Course::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $courses->map(fn ($course) => [
                'value' => $course->id,
                'label' => $course->name,
            ]),
        ]);
    }

    public function semestral(Request $request)
    {
        $validated = $request->validate([
            'semester' => 'required|string',
            'courses' => 'nullable|array',
            'courses.*' => 'integer',
            'types' => 'nullable|array',
            'types.*' => 'integer',
            'situations' => 'nullable|array',
            'situations.*' => 'string',
            'format' => 'required|in:browser,excel',
        ]);

        // Parse semester (e.g., "2026.1" -> year: 2026, semester: 1)
        [$year, $semester] = explode('.', $validated['semester']);

        // Calculate date range for the semester
        if ($semester == 1) {
            // First semester: January 1 to June 30
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-06-30 23:59:59";
        } else {
            // Second semester: July 1 to December 31
            $startDate = "{$year}-07-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";
        }

        // Query users with their registrations in the given semester
        $query = User::query()
            ->whereHas('registrations', function ($q) use ($startDate, $endDate, $validated) {
                $q->whereHas('seminar', function ($sq) use ($startDate, $endDate, $validated) {
                    $sq->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->where('active', true);

                    // Filter by seminar types if provided
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
                            'seminar:id,name,scheduled_at',
                            'seminar.seminarType:id,name',
                        ]);
                },
            ]);

        // Filter by course if provided
        if (! empty($validated['courses'])) {
            $query->whereHas('studentData', function ($q) use ($validated) {
                $q->whereIn('course_id', $validated['courses']);
            });
        }

        // Filter by course situation if provided
        if (! empty($validated['situations'])) {
            $query->whereHas('studentData', function ($q) use ($validated) {
                $q->whereIn('course_situation', $validated['situations']);
            });
        }

        $users = $query->get();

        // Calculate report data
        $reportData = $users->map(function ($user) {
            $registrations = $user->registrations;
            $totalHours = $registrations->sum(function ($registration) {
                // Assuming each seminar is 1 hour by default
                // You can adjust this based on actual seminar duration
                return 1;
            });

            $presentations = $registrations->map(function ($registration) {
                return [
                    'name' => $registration->seminar->name,
                    'date' => $registration->seminar->scheduled_at,
                    'type' => $registration->seminar->seminarType?->name,
                ];
            })->sortBy('date')->values();

            return [
                'name' => $user->name,
                'email' => $user->email,
                'course' => $user->studentData?->course?->name ?? 'N/A',
                'total_hours' => $totalHours,
                'presentations' => $presentations,
            ];
        });

        // Sort by name
        $reportData = $reportData->sortBy('name')->values();

        if ($validated['format'] === 'excel') {
            // Generate Excel file
            $timestamp = now()->format('YmdHis');
            $filename = "reports/{$timestamp}-relatorio-semestral-{$year}-{$semester}.xlsx";

            // Store in S3
            Excel::store(
                new SemestralReportExport($reportData, $year, $semester),
                $filename,
                's3',
                \Maatwebsite\Excel\Excel::XLSX
            );

            // Generate signed URL (valid for 1 hour)
            $url = Storage::disk('s3')->temporaryUrl($filename, now()->addHour());

            return response()->json([
                'message' => 'Relatório gerado com sucesso',
                'url' => $url,
            ]);
        }

        // Return JSON for browser view
        return response()->json([
            'data' => $reportData,
            'summary' => [
                'total_users' => $reportData->count(),
                'total_hours' => $reportData->sum('total_hours'),
                'semester' => $validated['semester'],
            ],
        ]);
    }
}
