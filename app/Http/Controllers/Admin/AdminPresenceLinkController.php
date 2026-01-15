<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PresenceLink;
use App\Models\Seminar;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminPresenceLinkController extends Controller
{
    public function show(Seminar $seminar): JsonResponse
    {
        Gate::authorize('viewAny', Seminar::class);

        $presenceLink = $seminar->presenceLink;

        if (! $presenceLink) {
            return response()->json([
                'data' => null,
            ]);
        }

        $url = url("/p/{$presenceLink->uuid}");

        // Generate QR code
        $options = new QROptions([
            'level' => 7,
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 20,
            'outputBase64' => true,
        ]);

        $qrcode = new QRCode($options);
        $qrCodeBase64 = $qrcode->render($url);

        return response()->json([
            'data' => [
                'id' => $presenceLink->id,
                'uuid' => $presenceLink->uuid,
                'active' => $presenceLink->active,
                'expires_at' => $presenceLink->expires_at?->toISOString(),
                'is_expired' => $presenceLink->isExpired(),
                'is_valid' => $presenceLink->isValid(),
                'url' => $url,
                'qr_code' => $qrCodeBase64,
            ],
        ]);
    }

    public function store(Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        // Check if presence link already exists
        if ($seminar->presenceLink) {
            return response()->json([
                'message' => 'Presence link already exists for this seminar',
            ], 409);
        }

        // Calculate expiration time: 4 hours after scheduled_at
        $expiresAt = $seminar->scheduled_at?->addHours(4);

        $presenceLink = PresenceLink::create([
            'seminar_id' => $seminar->id,
            'active' => false, // Default to inactive
            'expires_at' => $expiresAt,
        ]);

        $url = url("/p/{$presenceLink->uuid}");

        // Generate QR code
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 8,
        ]);

        $qrcode = new QRCode($options);
        $qrCodeBase64 = base64_encode($qrcode->render($url));

        return response()->json([
            'message' => 'Presence link created successfully',
            'data' => [
                'id' => $presenceLink->id,
                'uuid' => $presenceLink->uuid,
                'active' => $presenceLink->active,
                'expires_at' => $presenceLink->expires_at?->toISOString(),
                'is_expired' => $presenceLink->isExpired(),
                'is_valid' => $presenceLink->isValid(),
                'url' => $url,
                'qr_code' => "$qrCodeBase64",
            ],
        ], 201);
    }

    public function toggle(Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        $presenceLink = $seminar->presenceLink;

        if (! $presenceLink) {
            return response()->json([
                'message' => 'Presence link not found',
            ], 404);
        }

        $presenceLink->update([
            'active' => ! $presenceLink->active,
            'expires_at' => ! $presenceLink->active ? $seminar->scheduled_at?->addHours(4) : null,
        ]);

        return response()->json([
            'message' => 'Presence link status updated successfully',
            'data' => [
                'id' => $presenceLink->id,
                'uuid' => $presenceLink->uuid,
                'active' => $presenceLink->active,
                'expires_at' => $presenceLink->expires_at?->toISOString(),
                'is_expired' => $presenceLink->isExpired(),
                'is_valid' => $presenceLink->isValid(),
            ],
        ]);
    }
}
