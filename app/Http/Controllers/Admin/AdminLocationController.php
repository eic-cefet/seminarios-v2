<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLocationStoreRequest;
use App\Http\Requests\Admin\AdminLocationUpdateRequest;
use App\Http\Resources\Admin\AdminLocationResource;
use App\Models\SeminarLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminLocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SeminarLocation::class);

        $locations = SeminarLocation::withCount('seminars')
            ->orderBy('name')
            ->paginate(15);

        return AdminLocationResource::collection($locations);
    }

    public function show(SeminarLocation $location): AdminLocationResource
    {
        Gate::authorize('view', $location);

        $location->loadCount('seminars');

        return new AdminLocationResource($location);
    }

    public function store(AdminLocationStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $location = SeminarLocation::create($validated);
        $location->loadCount('seminars');

        return response()->json([
            'message' => 'Local criado com sucesso',
            'data' => new AdminLocationResource($location),
        ], 201);
    }

    public function update(AdminLocationUpdateRequest $request, SeminarLocation $location): JsonResponse
    {
        $validated = $request->validated();

        $location->update($validated);
        $location->loadCount('seminars');

        return response()->json([
            'message' => 'Local atualizado com sucesso',
            'data' => new AdminLocationResource($location),
        ]);
    }

    public function destroy(SeminarLocation $location): JsonResponse
    {
        Gate::authorize('delete', $location);

        $location->delete();

        return response()->json([
            'message' => 'Local excluído com sucesso',
        ]);
    }
}
