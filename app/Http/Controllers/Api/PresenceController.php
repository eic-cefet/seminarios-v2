<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresenceLink;
use App\Models\Registration;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PresenceController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService,
    ) {}

    public function show(string $uuid): JsonResponse
    {
        $presenceLink = PresenceLink::where('uuid', $uuid)
            ->with('seminar')
            ->first();

        if (! $presenceLink) {
            return response()->json([
                'message' => 'Link de presença não encontrado',
            ], 404);
        }

        if (! $presenceLink->isValid()) {
            return response()->json([
                'message' => 'Link de presença inválido ou expirado',
                'is_valid' => false,
                'is_expired' => $presenceLink->isExpired(),
                'is_active' => $presenceLink->active,
            ], 400);
        }

        return response()->json([
            'data' => [
                'seminar' => [
                    'id' => $presenceLink->seminar->id,
                    'name' => $presenceLink->seminar->name,
                    'scheduled_at' => $presenceLink->seminar->scheduled_at?->toISOString(),
                ],
                'is_valid' => true,
                'expires_at' => $presenceLink->expires_at?->toISOString(),
            ],
        ]);
    }

    public function register(Request $request, string $uuid): JsonResponse
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json([
                'message' => 'Você precisa estar autenticado para registrar presença',
                'requires_auth' => true,
            ], 401);
        }

        $presenceLink = PresenceLink::where('uuid', $uuid)
            ->with('seminar')
            ->first();

        if (! $presenceLink) {
            return response()->json([
                'message' => 'Link de presença não encontrado',
            ], 404);
        }

        // Check if link is valid (active and not expired)
        if (! $presenceLink->isValid()) {
            return response()->json([
                'message' => 'Link de presença inválido ou expirado',
                'is_valid' => false,
                'is_expired' => $presenceLink->isExpired(),
                'is_active' => $presenceLink->active,
            ], 400);
        }

        $user = $request->user();
        $seminar = $presenceLink->seminar;

        // Find or create registration for this seminar
        $registration = Registration::firstOrCreate(
            [
                'seminar_id' => $seminar->id,
                'user_id' => $user->id,
            ],
            [
                'present' => true,
            ]
        );

        // If already present, return success (idempotent)
        if ($registration->wasRecentlyCreated || $registration->present) {
            return response()->json([
                'message' => 'Presença registrada com sucesso!',
                'data' => [
                    'present' => true,
                    'seminar' => [
                        'id' => $seminar->id,
                        'name' => $seminar->name,
                    ],
                ],
            ]);
        }

        // Mark as present if not already
        $registration->update(['present' => true]);

        return response()->json([
            'message' => 'Presença registrada com sucesso!',
            'data' => [
                'present' => true,
                'seminar' => [
                    'id' => $seminar->id,
                    'name' => $seminar->name,
                ],
            ],
        ]);
    }

    public function qrCodePng(string $uuid): Response
    {
        $presenceLink = PresenceLink::where('uuid', $uuid)->first();

        if (! $presenceLink) {
            abort(404, 'Link de presença não encontrado');
        }

        // Only allow PNG download for active and non-expired links
        if (! $presenceLink->isValid()) {
            abort(400, 'Link de presença inválido ou expirado');
        }

        $url = url("/p/{$presenceLink->uuid}");
        $pngData = $this->qrCodeService->toPng($url);

        return response($pngData)->header('Content-Type', 'image/png');
    }
}
