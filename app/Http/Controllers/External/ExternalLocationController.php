<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalLocationStoreRequest;
use App\Http\Requests\External\ExternalLocationUpdateRequest;
use App\Http\Resources\External\ExternalLocationResource;
use App\Models\SeminarLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExternalLocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SeminarLocation::class);

        return ExternalLocationResource::collection(
            SeminarLocation::orderBy('name')->get()
        );
    }

    public function show(SeminarLocation $location): ExternalLocationResource
    {
        Gate::authorize('view', $location);

        return new ExternalLocationResource($location);
    }

    public function store(ExternalLocationStoreRequest $request): JsonResponse
    {
        $location = SeminarLocation::create($request->validated());

        return response()->json([
            'message' => 'Location created successfully.',
            'data' => (new ExternalLocationResource($location))->resolve($request),
        ], 201);
    }

    public function update(ExternalLocationUpdateRequest $request, SeminarLocation $location): JsonResponse
    {
        $location->update($request->validated());

        return response()->json([
            'message' => 'Location updated successfully.',
            'data' => (new ExternalLocationResource($location))->resolve($request),
        ]);
    }
}
