<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        $user->load('studentData.course');

        return response()->json([
            'user' => [
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
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

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
            'user' => [
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
            ],
        ]);
    }

    /**
     * Update the authenticated user's student data
     */
    public function updateStudentData(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'course_situation' => ['required', 'in:studying,graduated'],
            'course_role' => ['required', 'in:Aluno,Professor,Outro'],
        ]);

        if ($user->studentData) {
            $user->studentData->update($validated);
        } else {
            $user->studentData()->create($validated);
        }

        $user->load('studentData.course');

        return response()->json([
            'message' => 'Dados atualizados com sucesso.',
            'user' => [
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
            ],
        ]);
    }

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

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
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

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
     * Get the authenticated user's certificates
     */
    public function certificates(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

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
}
