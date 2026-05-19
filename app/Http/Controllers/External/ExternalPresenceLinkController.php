<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Resources\External\ExternalPresenceLinkResource;
use App\Models\PresenceLink;
use App\Models\Seminar;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExternalPresenceLinkController extends Controller
{
    /**
     * Read the presence link for a seminar.
     *
     * Returns the seminar's presence-link metadata: the public URL attendees
     * open (or scan as a QR code), the PNG URL for direct download, and the
     * link's active/expiry/validity flags.
     *
     * - If the seminar exists but has no presence link, the response is
     *   `{ "data": null }` with `200 OK`. A missing seminar returns `404`.
     * - Pass `?include=qr_code` to receive the QR PNG inline as a
     *   `data:image/png;base64,...` URI (scale 20, matches the admin panel).
     *   Skipped by default since a base64 PNG is ~25-60 KB.
     * - Conditional requests: the response sets `Last-Modified` / `ETag`
     *   based on `max(seminar.updated_at, presenceLink.updated_at)`. Replay
     *   them on the next request to receive `304 Not Modified`.
     */
    #[QueryParameter('include', description: 'Comma-separated opt-in fields. Only `qr_code` is recognized — when set, the response includes a `qr_code` field with the QR PNG as a base64 data URI (scale 20). Any other value returns 422.', type: 'string', example: 'qr_code')]
    public function show(Request $request, Seminar $seminar): JsonResponse
    {
        Gate::authorize('view', $seminar);

        $presenceLink = $seminar->presenceLink;

        $lastModified = $presenceLink?->updated_at && $seminar->updated_at
            ? max($presenceLink->updated_at, $seminar->updated_at)
            : $presenceLink?->updated_at ?? $seminar->updated_at;
        $request->attributes->set('external_last_modified', $lastModified);

        if (! $presenceLink) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => (new ExternalPresenceLinkResource($presenceLink, includeQrCode: $this->wantsQrCode($request)))
                ->toArray($request),
        ]);
    }

    /**
     * Create the presence link for a seminar (idempotent).
     *
     * The request body is empty — the link is fully derived from the parent
     * seminar:
     *
     * - `uuid` is server-generated.
     * - `active` defaults to `false`. Call `PATCH .../presence-link` with
     *   `{ "active": true }` to activate.
     * - `expires_at` defaults to `seminar.scheduled_at + 4h` when the
     *   seminar has a scheduled date, otherwise `null`.
     *
     * Idempotent — safe to call defensively:
     * - First call returns `201 Created` with the freshly-minted link.
     * - Subsequent calls return `200 OK` with the existing record
     *   unchanged. No `409 Conflict` is raised.
     *
     * The standard `Idempotency-Key` header is supported on this endpoint
     * (see the "Idempotency" section of the getting-started guide). The QR
     * base64 is NOT returned here — fetch `GET .../presence-link?include=qr_code`
     * afterward if you need it.
     */
    public function store(Request $request, Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        if ($existing = $seminar->presenceLink) {
            return response()->json([
                'message' => 'Presence link already exists.',
                'data' => (new ExternalPresenceLinkResource($existing))->toArray($request),
            ], 200);
        }

        $presenceLink = PresenceLink::create([
            'seminar_id' => $seminar->id,
            'active' => false,
            'expires_at' => $seminar->scheduled_at?->addHours(4),
        ]);

        return response()->json([
            'message' => 'Presence link created successfully.',
            'data' => (new ExternalPresenceLinkResource($presenceLink))->toArray($request),
        ], 201);
    }

    private function wantsQrCode(Request $request): bool
    {
        $include = trim((string) $request->query('include', ''));
        if ($include === '') {
            return false;
        }

        $tokens = array_filter(array_map('trim', explode(',', $include)));
        foreach ($tokens as $token) {
            if ($token !== 'qr_code') {
                throw ValidationException::withMessages([
                    'include' => "Unknown include '{$token}'",
                ]);
            }
        }

        return in_array('qr_code', $tokens, true);
    }
}
