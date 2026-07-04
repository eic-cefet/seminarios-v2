<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SemestralReportRequest;
use App\Jobs\GenerateSemestralReportJob;
use App\Models\AuditLog;
use App\Models\Course;
use App\Services\SemestralReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly SemestralReportService $semestralReports,
    ) {}

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

    public function semestral(SemestralReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'courses' => $validated['courses'] ?? [],
            'types' => $validated['types'] ?? [],
            'situations' => $validated['situations'] ?? [],
        ];

        if ($validated['format'] === 'excel') {
            GenerateSemestralReportJob::dispatch(
                $request->user(),
                $validated['semester'],
                $filters['courses'],
                $filters['types'],
                $filters['situations'],
            );

            AuditLog::record(AuditEvent::ReportQueued, eventData: [
                'type' => 'semestral',
                'semester' => $validated['semester'],
                'format' => 'excel',
                'filters' => array_filter($filters),
            ]);

            return response()->json([
                'message' => 'Relatório sendo gerado. Você receberá um e-mail em breve.',
            ]);
        }

        $reportData = $this->semestralReports->collect($validated['semester'], $filters);

        AuditLog::record(AuditEvent::ReportGenerated, eventData: [
            'semester' => $validated['semester'],
            'format' => $validated['format'],
            'filters' => array_filter($filters),
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
