<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalLocationStoreRequest;
use App\Http\Requests\External\ExternalLocationUpdateRequest;
use App\Http\Resources\External\ExternalLocationResource;
use App\Models\SeminarLocation;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExternalLocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SeminarLocation::class);

        $locations = SeminarLocation::orderBy('name')->get();

        $lastModified = $locations->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return ExternalLocationResource::collection($locations)
            ->additional(['meta' => ['total' => $locations->count()]]);
    }

    public function show(Request $request, SeminarLocation $location): ExternalLocationResource
    {
        Gate::authorize('view', $location);

        $request->attributes->set('external_last_modified', $location->updated_at);

        return new ExternalLocationResource($location);
    }

    #[BodyParameter('name', description: 'Display name (must be unique)', type: 'string', example: 'Auditório Principal')]
    #[BodyParameter('max_vacancies', description: 'Seating capacity', type: 'integer', example: 200)]
    public function store(ExternalLocationStoreRequest $request): JsonResponse
    {
        $location = SeminarLocation::create($request->validated());

        return response()->json([
            'message' => 'Location created successfully.',
            'data' => (new ExternalLocationResource($location))->resolve($request),
        ], 201);
    }

    #[BodyParameter('name', description: 'Display name (must be unique)', type: 'string', example: 'Auditório Principal')]
    #[BodyParameter('max_vacancies', description: 'Seating capacity', type: 'integer', example: 200)]
    public function update(ExternalLocationUpdateRequest $request, SeminarLocation $location): JsonResponse
    {
        $location->update($request->validated());

        return response()->json([
            'message' => 'Location updated successfully.',
            'data' => (new ExternalLocationResource($location))->resolve($request),
        ]);
    }
}
