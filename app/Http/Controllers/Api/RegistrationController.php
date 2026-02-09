<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Seminar;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    private function findSeminar(string $slug): Seminar
    {
        $seminar = Seminar::where('slug', $slug)
            ->where('active', true)
            ->first();

        if (! $seminar) {
            throw ApiException::notFound('Seminário');
        }

        return $seminar;
    }

    /**
     * Register for a seminar
     */
    public function register(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $seminar = $this->findSeminar($slug);

        // Check if seminar is expired
        if ($seminar->scheduled_at->isPast()) {
            throw ApiException::seminarExpired();
        }

        // Check if seminar has reached capacity
        $seminar->load('seminarLocation');
        if ($seminar->seminarLocation?->max_vacancies) {
            $registrationCount = $seminar->registrations()->count();
            if ($registrationCount >= $seminar->seminarLocation->max_vacancies) {
                throw ApiException::seminarFull();
            }
        }

        // Create registration (unique constraint on seminar_id+user_id prevents duplicates)
        try {
            $registration = DB::transaction(fn () => $seminar->registrations()->create([
                'user_id' => $user->id,
                'present' => false,
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ApiException::alreadyRegistered();
        }

        return response()->json([
            'message' => 'Inscrição realizada com sucesso',
            'registration' => [
                'id' => $registration->id,
                'seminar_id' => $registration->seminar_id,
                'created_at' => $registration->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Cancel registration for a seminar
     */
    public function unregister(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $seminar = $this->findSeminar($slug);

        $registration = $seminar->registrations()
            ->where('user_id', $user->id)
            ->first();

        if (! $registration) {
            throw ApiException::notRegistered();
        }

        $registration->delete();

        return response()->json([
            'message' => 'Inscrição cancelada com sucesso',
        ]);
    }

    /**
     * Check registration status for a seminar
     */
    public function status(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        $seminar = $this->findSeminar($slug);

        if (! $user) {
            return response()->json([
                'registered' => false,
            ]);
        }

        $registration = $seminar->registrations()
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'registered' => (bool) $registration,
            'registration' => $registration ? [
                'id' => $registration->id,
                'created_at' => $registration->created_at->toISOString(),
            ] : null,
        ]);
    }
}
