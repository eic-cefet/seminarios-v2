<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStudentSemesterRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\StudentAiSummaryService;
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class AdminStudentController extends Controller
{
    public function __construct(
        private readonly StudentDashboardService $dashboard,
        private readonly StudentAiSummaryService $aiSummary,
    ) {}

    public function index(AdminStudentSemesterRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $range = $request->semesterRange();
        $students = $this->dashboard->listStudents(
            $range,
            $request->user(),
            $request->validated('search'),
        );

        return response()->json([
            'data' => $students->getCollection()
                ->map(fn (User $student) => $this->mapStudentSummary($student))
                ->all(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
            'summary' => ['semester' => $range->toString()],
        ]);
    }

    public function show(AdminStudentSemesterRequest $request, User $user): JsonResponse
    {
        Gate::authorize('viewStudentDashboard', $user);

        $range = $request->semesterRange();
        $data = $this->dashboard->forStudent($user, $range, $request->user());

        AuditLog::record(AuditEvent::StudentDashboardViewed, auditable: $user, eventData: [
            'semester' => $range->toString(),
        ]);

        $registrations = $data['registrations']->map(fn ($registration) => [
            'id' => $registration->id,
            'present' => $registration->present,
            'status' => match (true) {
                $registration->present => 'attended',
                $registration->seminar->scheduled_at->isFuture() => 'upcoming',
                default => 'missed',
            },
            'certificate_code' => $registration->certificate_code,
            'seminar' => [
                'id' => $registration->seminar->id,
                'name' => $registration->seminar->name,
                'scheduled_at' => $registration->seminar->scheduled_at->toISOString(),
                'duration_minutes' => (int) ($registration->seminar->duration_minutes ?? 60),
                'seminar_type' => $registration->seminar->seminarType?->name,
            ],
        ])->values();

        $certificates = $data['registrations']
            ->filter(fn ($registration) => $registration->present && $registration->certificate_code)
            ->map(fn ($registration) => [
                'id' => $registration->id,
                'certificate_code' => $registration->certificate_code,
                'seminar_name' => $registration->seminar->name,
                'download_url' => route('certificate.show', ['code' => $registration->certificate_code]),
            ])->values();

        return response()->json([
            'data' => [
                'student' => $this->mapStudentSummary($user),
                'semester' => $range->toString(),
                'totals' => $data['totals'],
                'by_type' => $data['by_type'],
                'registrations' => $registrations,
                'certificates' => $certificates,
            ],
        ]);
    }

    public function aiSummary(AdminStudentSemesterRequest $request, User $user): JsonResponse
    {
        Gate::authorize('viewStudentDashboard', $user);

        $range = $request->semesterRange();

        try {
            $summary = $this->aiSummary->summaryFor($user, $range, $request->user());
        } catch (RuntimeException) {
            return response()->json([
                'error' => 'ai_request_failed',
                'message' => 'AI service request failed. Please try again.',
            ], 502);
        }

        if ($summary === null) {
            return response()->json([
                'error' => 'ai_not_configured',
                'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
            ], 503);
        }

        AuditLog::record(AuditEvent::AiStudentSummaryGenerated, auditable: $user, eventData: [
            'semester' => $range->toString(),
        ]);

        return response()->json(['data' => ['summary' => $summary]]);
    }

    /**
     * @return array{id: int, name: string, email: string, course: string, course_situation: ?string}
     */
    private function mapStudentSummary(User $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'course' => $student->studentData?->course?->name ?? 'N/A',
            'course_situation' => $student->studentData?->course_situation?->value,
        ];
    }
}
