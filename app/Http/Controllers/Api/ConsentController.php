<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConsentRequest;
use App\Http\Resources\ConsentResource;
use App\Models\AuditLog;
use App\Models\UserConsent;
use App\Services\IpHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $consents = $request->user()
            ->consents()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => ConsentResource::collection($consents),
        ]);
    }

    public function store(ConsentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $hasher = app(IpHasher::class);

        $consent = UserConsent::create([
            'user_id' => $user?->id,
            'anonymous_id' => $user ? null : ($validated['anonymous_id'] ?? null),
            'type' => $validated['type'],
            'granted' => $validated['granted'],
            'version' => $validated['version'] ?? null,
            'ip_hash' => $hasher->hash($request->ip()),
            'user_agent_hash' => $hasher->hashOpaque((string) $request->userAgent()),
            'source' => $user ? 'preference_center' : 'cookie_banner',
        ]);

        AuditLog::record(
            event: $consent->granted ? AuditEvent::ConsentGranted : AuditEvent::ConsentRevoked,
            auditable: $consent,
        );

        return response()->json(['data' => new ConsentResource($consent)], 201);
    }
}
