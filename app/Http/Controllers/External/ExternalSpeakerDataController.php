<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSpeakerDataUpdateRequest;
use App\Http\Resources\External\ExternalSpeakerDataResource;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Services\SlugService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalSpeakerDataController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function show(Request $request, User $user): JsonResponse
    {
        $userUpdated = $user->updated_at;
        $speakerUpdated = $user->speakerData?->updated_at ?? $userUpdated;
        $request->attributes->set(
            'external_last_modified',
            $userUpdated && $speakerUpdated && $userUpdated->greaterThan($speakerUpdated)
                ? $userUpdated
                : $speakerUpdated
        );

        if (! $user->speakerData) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new ExternalSpeakerDataResource($user->speakerData),
        ]);
    }

    #[BodyParameter('institution', description: 'Speaker institution or affiliation', type: 'string', example: 'CEFET-RJ')]
    #[BodyParameter('description', description: 'Speaker biography (Markdown supported)', type: 'string', example: 'Professor of distributed systems with 10+ years of research experience.')]
    public function update(ExternalSpeakerDataUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

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
