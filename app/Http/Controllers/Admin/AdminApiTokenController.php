<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AdminApiTokenController extends Controller
{
    public const AVAILABLE_ABILITIES = [
        'seminars:read',
        'seminars:write',
        'seminar-types:read',
        'seminar-types:write',
        'locations:read',
        'locations:write',
        'users:read',
        'users:write',
        'speaker-data:read',
        'speaker-data:write',
    ];

    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->latest()
            ->paginate(15);

        $mapped = $tokens->through(fn (PersonalAccessToken $token) => [
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $mapped->items(),
            'meta' => [
                'current_page' => $tokens->currentPage(),
                'last_page' => $tokens->lastPage(),
                'per_page' => $tokens->perPage(),
                'total' => $tokens->total(),
                'from' => $tokens->firstItem(),
                'to' => $tokens->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'in:7,30,60,90,180'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', Rule::in(self::AVAILABLE_ABILITIES)],
        ]);

        $user = $request->user();
        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays($validated['expires_in_days'])
            : null;
        $abilities = ! empty($validated['abilities']) ? $validated['abilities'] : ['*'];
        $token = $user->createToken($validated['name'], $abilities, $expiresAt);

        AuditLog::record(AuditEvent::ApiTokenCreated, auditable: $user, eventData: [
            'token_name' => $validated['name'],
            'abilities' => $abilities,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Token criado com sucesso. Guarde-o em um local seguro, pois não será possível visualizá-lo novamente.',
            'data' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
                'token' => Str::after($token->plainTextToken, '|'),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $token = $request->user()
            ->tokens()
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', Rule::in(self::AVAILABLE_ABILITIES)],
        ]);

        if (isset($validated['name'])) {
            $token->name = $validated['name'];
        }

        if (isset($validated['abilities'])) {
            $token->abilities = ! empty($validated['abilities']) ? $validated['abilities'] : ['*'];
        }

        $token->save();

        AuditLog::record(AuditEvent::ApiTokenUpdated, auditable: $request->user(), eventData: [
            'token_name' => $token->name,
            'abilities' => $token->abilities,
        ]);

        return response()->json([
            'message' => 'Token atualizado com sucesso.',
            'data' => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $token = $request->user()
            ->tokens()
            ->findOrFail($id);

        AuditLog::record(AuditEvent::ApiTokenRevoked, auditable: $request->user(), eventData: [
            'token_name' => $token->name,
        ]);

        $token->delete();

        return response()->json([
            'message' => 'Token revogado com sucesso.',
        ]);
    }

    public function abilities(): JsonResponse
    {
        return response()->json([
            'data' => self::AVAILABLE_ABILITIES,
        ]);
    }
}
