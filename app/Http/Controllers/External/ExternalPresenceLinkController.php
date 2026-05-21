<?php

namespace App\Http\Controllers\External;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalPresenceLinkUpdateRequest;
use App\Http\Resources\External\ExternalPresenceLinkResource;
use App\Models\PresenceLink;
use App\Models\Seminar;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use App\Support\PresenceLinkPolicy;
use Dedoc\Scramble\Attributes\BodyParameter;
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
            'data' => new ExternalPresenceLinkResource($presenceLink, includeQrCode: $this->wantsQrCode($request)),
        ]);
    }

    /**
     * Create the presence link for a seminar (idempotent).
     *
     * The request body is empty — the link is fully derived from the parent
     * seminar:
     *
     * - `uuid` is server-generated.
     * - `active` defaults to `true` so the link is usable immediately.
     *   Call `PATCH .../presence-link` with `{ "active": false }` to
     *   deactivate later.
     * - `expires_at` defaults to `max(scheduled_at + 4h, now() + 1h)` —
     *   the same policy `PATCH { active: true }` applies on toggle. If the
     *   seminar has no `scheduled_at`, the expiry falls back to `now + 1h`,
     *   guaranteeing the freshly-created link is valid for at least an
     *   hour.
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

        // Serialize concurrent POSTs for the same seminar through the
        // project's standard Mutex (Cache-backed). Two simultaneous callers
        // would otherwise both pass the existence check and double-create —
        // presence_links.seminar_id has no DB-level unique constraint today.
        [$presenceLink, $created] = Mutex::for(LockKey::presenceLinkCreation($seminar->id))
            ->protect(function () use ($seminar) {
                if ($existing = $seminar->fresh()->presenceLink) {
                    return [$existing, false];
                }

                $link = PresenceLink::create([
                    'seminar_id' => $seminar->id,
                    'active' => PresenceLinkPolicy::defaultActive(),
                    'expires_at' => PresenceLinkPolicy::expiresAt($seminar),
                ]);

                return [$link, true];
            });

        return response()->json([
            'message' => $created
                ? 'Presence link created successfully.'
                : 'Presence link already exists.',
            'data' => new ExternalPresenceLinkResource($presenceLink),
        ], $created ? 201 : 200);
    }

    /**
     * Update the presence link's active state and/or expiry.
     *
     * Accepts both `PATCH` and `PUT` (route registered via `Route::match`).
     * Body must include at least one of `active` or `expires_at`; an empty
     * body or a body with only unrecognized fields returns `422`.
     *
     * **Implicit expiry policy.** When `active` is sent and `expires_at` is
     * NOT sent, the server computes `expires_at` so the one-liner case
     * matches the admin panel:
     *
     * | Sent `active` | Sent `expires_at`? | Resulting `expires_at` |
     * | ------------- | ------------------ | ---------------------- |
     * | `true`        | no                 | `max(scheduled_at + 4h, now() + 1h)` |
     * | `false`       | no                 | `null` |
     * | (any)         | yes                | the value sent (including `null`) |
     *
     * If the seminar has no presence link to update, returns
     * `404 not_found` — call `POST .../presence-link` first to create it.
     */
    #[BodyParameter('active', description: 'New active state. When sent without `expires_at`, the server auto-computes the expiry: `true` → `max(scheduled_at + 4h, now() + 1h)`; `false` → `null`. To override the auto-compute, send `expires_at` explicitly.', type: 'boolean', example: true)]
    #[BodyParameter('expires_at', description: 'Explicit expiry override (ISO-8601 datetime, or `null` to clear). When present in the request body, this value wins over the auto-compute branch — even when `active` is also sent.', type: 'string', example: '2026-06-15T20:00:00Z')]
    public function update(ExternalPresenceLinkUpdateRequest $request, Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        $presenceLink = $seminar->presenceLink;

        if (! $presenceLink) {
            throw ApiException::notFound('Presence link not found');
        }

        $validated = $request->validated();
        $updates = [];

        if ($request->has('active')) {
            $newActive = (bool) $validated['active'];
            $updates['active'] = $newActive;

            if (! $request->has('expires_at')) {
                $updates['expires_at'] = $newActive
                    ? PresenceLinkPolicy::expiresAt($seminar)
                    : null;
            }
        }

        if ($request->has('expires_at')) {
            $updates['expires_at'] = $validated['expires_at'] ?? null;
        }

        $presenceLink->update($updates);

        return response()->json([
            'message' => 'Presence link updated successfully.',
            'data' => new ExternalPresenceLinkResource($presenceLink->fresh()),
        ]);
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
