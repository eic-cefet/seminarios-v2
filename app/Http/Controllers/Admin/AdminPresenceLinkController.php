<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PresenceLinkResource;
use App\Models\PresenceLink;
use App\Models\Seminar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class AdminPresenceLinkController extends Controller
{
    public function show(Seminar $seminar): JsonResource|JsonResponse
    {
        Gate::authorize('viewAny', Seminar::class);

        $presenceLink = $seminar->presenceLink;

        if (! $presenceLink) {
            return response()->json([
                'data' => null,
            ]);
        }

        return new PresenceLinkResource($presenceLink, includeQrCode: true, qrCodeScale: 20);
    }

    public function store(Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        if ($seminar->presenceLink) {
            return response()->json([
                'message' => 'Presence link already exists for this seminar',
            ], 409);
        }

        $expiresAt = $seminar->scheduled_at?->addHours(4);

        $presenceLink = PresenceLink::create([
            'seminar_id' => $seminar->id,
            'active' => false, // Default to inactive
            'expires_at' => $expiresAt,
        ]);

        return (new PresenceLinkResource($presenceLink, includeQrCode: true))
            ->additional(['message' => 'Presence link created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function toggle(Seminar $seminar): JsonResource|JsonResponse
    {
        Gate::authorize('update', $seminar);

        $presenceLink = $seminar->presenceLink;

        if (! $presenceLink) {
            return response()->json([
                'message' => 'Presence link not found',
            ], 404);
        }

        // When activating, expire at the later of: scheduled_at + 4h OR now + 1h
        $expiresAt = null;
        if (! $presenceLink->active) {
            $scheduledExpiry = $seminar->scheduled_at?->addHours(4);
            $minimumExpiry = now()->addHour();
            $expiresAt = $scheduledExpiry && $scheduledExpiry->gt($minimumExpiry)
                ? $scheduledExpiry
                : $minimumExpiry;
        }

        $presenceLink->update([
            'active' => ! $presenceLink->active,
            'expires_at' => $expiresAt,
        ]);

        return (new PresenceLinkResource($presenceLink))
            ->additional(['message' => 'Presence link status updated successfully']);
    }
}
