<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Seminar;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    private function findSeminar(string $slug): Seminar
    {
        $seminar = Seminar::where('slug', $slug)
            ->where('active', true)
            ->first();

        if (! $seminar) {
            throw ApiException::notFound(message: 'Apresentação não encontrada');
        }

        return $seminar;
    }

    public function register(Request $request, string $slug, RegistrationService $service): JsonResponse
    {
        $registration = $service->register($request->user(), $this->findSeminar($slug));

        return response()->json([
            'message' => 'Inscrição realizada com sucesso',
            'registration' => [
                'id' => $registration->id,
                'seminar_id' => $registration->seminar_id,
                'created_at' => $registration->created_at->toISOString(),
            ],
        ], 201);
    }

    public function unregister(Request $request, string $slug, RegistrationService $service): JsonResponse
    {
        $service->unregister($request->user(), $this->findSeminar($slug));

        return response()->json(['message' => 'Inscrição cancelada com sucesso']);
    }

    public function status(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $seminar = $this->findSeminar($slug);

        if (! $user) {
            return response()->json(['registered' => false]);
        }

        $registration = $seminar->registrations()
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'registered' => $registration !== null,
            'registration' => $registration ? [
                'id' => $registration->id,
                'created_at' => $registration->created_at->toISOString(),
            ] : null,
        ]);
    }
}
