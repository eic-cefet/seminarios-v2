<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StudentDataUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();
        $user->load('studentData.course');

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $emailChanged = $user->email !== $validated['email'];

        $user->update($validated);

        // If email changed, clear verification
        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            // TODO: Send verification email
        }

        $user->load('studentData.course');

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Update the authenticated user's student data
     */
    public function updateStudentData(StudentDataUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->studentData) {
            $user->studentData->update($validated);
        } else {
            $user->studentData()->create($validated);
        }

        $user->load('studentData.course');

        return response()->json([
            'message' => 'Dados atualizados com sucesso.',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ApiException::mismatchedCredentials();
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Senha atualizada com sucesso.',
        ]);
    }

    /**
     * Get the authenticated user's registrations (seminars attended)
     */
    public function registrations(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();
        $perPage = $this->getPerPage($request);

        $paginator = $user->registrations()
            ->whereHas('seminar')
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($registration) => [
                'id' => $registration->id,
                'present' => $registration->present,
                'certificate_code' => $registration->certificate_code,
                'created_at' => $registration->created_at->toISOString(),
                'seminar' => [
                    'id' => $registration->seminar->id,
                    'name' => $registration->seminar->name,
                    'slug' => $registration->seminar->slug,
                    'scheduled_at' => $registration->seminar->scheduled_at?->toISOString(),
                    'is_expired' => $registration->seminar->scheduled_at?->isPast() ?? false,
                    'seminar_type' => $registration->seminar->seminarType ? [
                        'id' => $registration->seminar->seminarType->id,
                        'name' => $registration->seminar->seminarType->name,
                    ] : null,
                    'location' => $registration->seminar->seminarLocation ? [
                        'id' => $registration->seminar->seminarLocation->id,
                        'name' => $registration->seminar->seminarLocation->name,
                    ] : null,
                ],
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get the authenticated user's pending evaluations (seminars attended but not rated)
     */
    public function pendingEvaluations(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();

        // Get registrations where:
        // - User was present
        // - Seminar ended within the last 30 days
        // - User hasn't rated the seminar yet
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $registrations = $user->registrations()
            ->where('present', true)
            ->whereHas('seminar', function ($query) use ($thirtyDaysAgo) {
                $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>=', $thirtyDaysAgo);
            })
            ->whereDoesntHave('seminar.ratings', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->get();

        return response()->json([
            'data' => $registrations->map(fn ($registration) => [
                'id' => $registration->id,
                'seminar' => [
                    'id' => $registration->seminar->id,
                    'name' => $registration->seminar->name,
                    'slug' => $registration->seminar->slug,
                    'scheduled_at' => $registration->seminar->scheduled_at?->toISOString(),
                    'seminar_type' => $registration->seminar->seminarType ? [
                        'id' => $registration->seminar->seminarType->id,
                        'name' => $registration->seminar->seminarType->name,
                    ] : null,
                    'location' => $registration->seminar->seminarLocation ? [
                        'id' => $registration->seminar->seminarLocation->id,
                        'name' => $registration->seminar->seminarLocation->name,
                    ] : null,
                ],
            ]),
        ]);
    }

    /**
     * Submit a rating for a seminar
     */
    public function submitRating(Request $request, int $seminarId): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if user attended this seminar
        $registration = $user->registrations()
            ->where('seminar_id', $seminarId)
            ->where('present', true)
            ->first();

        if (! $registration) {
            throw ApiException::forbidden('Você não participou deste seminário.');
        }

        // Check if seminar is within 30 days
        $seminar = $registration->seminar;
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        if (! $seminar->scheduled_at || $seminar->scheduled_at < $thirtyDaysAgo) {
            throw ApiException::forbidden('O prazo para avaliar este seminário expirou.');
        }

        // Check if already rated
        $existingRating = \App\Models\Rating::query()
            ->where('seminar_id', $seminarId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            throw ApiException::conflict('Você já avaliou este seminário.');
        }

        // Create rating
        $rating = \App\Models\Rating::create([
            'seminar_id' => $seminarId,
            'user_id' => $user->id,
            'score' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Avaliação enviada com sucesso!',
            'rating' => [
                'id' => $rating->id,
                'score' => $rating->score,
                'comment' => $rating->comment,
            ],
        ]);
    }

    /**
     * Get the authenticated user's certificates
     */
    public function certificates(Request $request): JsonResponse
    {
        // auth:sanctum middleware ensures $user is not null
        $user = $request->user();
        $perPage = $this->getPerPage($request);

        $paginator = $user->registrations()
            ->whereHas('seminar')
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->with(['seminar.seminarType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($registration) => [
                'id' => $registration->id,
                'certificate_code' => $registration->certificate_code,
                'seminar' => [
                    'id' => $registration->seminar->id,
                    'name' => $registration->seminar->name,
                    'slug' => $registration->seminar->slug,
                    'scheduled_at' => $registration->seminar->scheduled_at?->toISOString(),
                    'seminar_type' => $registration->seminar->seminarType ? [
                        'id' => $registration->seminar->seminarType->id,
                        'name' => $registration->seminar->seminarType->name,
                    ] : null,
                ],
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'roles' => $user->getRoleNames()->toArray(),
            'student_data' => $user->studentData ? [
                'course_situation' => $user->studentData->course_situation,
                'course_role' => $user->studentData->course_role,
                'course' => $user->studentData->course ? [
                    'id' => $user->studentData->course->id,
                    'name' => $user->studentData->course->name,
                ] : null,
            ] : null,
        ];
    }
}
