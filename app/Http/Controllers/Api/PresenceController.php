<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\PresenceLink;
use App\Models\Registration;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService,
    ) {}

    public function show(string $uuid): JsonResponse
    {
        $presenceLink = $this->findValidPresenceLink($uuid);

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
        if (! $request->user()) {
            return response()->json([
                'message' => 'Você precisa estar autenticado para registrar presença',
                'requires_auth' => true,
            ], 401);
        }

        $presenceLink = $this->findValidPresenceLink($uuid);

        $user = $request->user();
        $seminar = $presenceLink->seminar;

        DB::transaction(function () use ($seminar, $user) {
            $registration = Registration::firstOrCreate(
                [
                    'seminar_id' => $seminar->id,
                    'user_id' => $user->id,
                ],
                [
                    'present' => true,
                ]
            );

            if (! $registration->wasRecentlyCreated && ! $registration->present) {
                $registration->update(['present' => true]);
            }
        });

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
        $presenceLink = $this->findValidPresenceLink($uuid);

        $url = url("/p/{$presenceLink->uuid}");
        $pngData = $this->qrCodeService->toPng($url);

        return response($pngData)->header('Content-Type', 'image/png');
    }

    private function findValidPresenceLink(string $uuid): PresenceLink
    {
        $presenceLink = PresenceLink::where('uuid', $uuid)
            ->with('seminar')
            ->first();

        if (! $presenceLink) {
            throw ApiException::notFound('Link de presença');
        }

        if (! $presenceLink->isValid()) {
            throw new ApiException(
                'invalid_presence_link',
                'Link de presença inválido ou expirado',
                400,
            );
        }

        return $presenceLink;
    }
}
