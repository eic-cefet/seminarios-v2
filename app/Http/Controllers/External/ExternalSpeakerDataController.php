<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Resources\External\ExternalSpeakerDataResource;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalSpeakerDataController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function show(User $user): JsonResponse
    {
        if (! $user->speakerData) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new ExternalSpeakerDataResource($user->speakerData),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'institution' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $speakerData = UserSpeakerData::updateOrCreate(
            ['user_id' => $user->id],
            [
                'slug' => $user->speakerData?->slug
                    ?? $this->slugService->generateUnique($user->name, UserSpeakerData::class),
                'institution' => $validated['institution'] ?? null,
                'description' => $validated['description'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Speaker data updated successfully.',
            'data' => new ExternalSpeakerDataResource($speakerData),
        ]);
    }
}
