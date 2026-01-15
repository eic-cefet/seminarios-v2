<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminLocationResource;
use App\Models\SeminarLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', SeminarLocation::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_vacancies' => ['required', 'integer', 'min:1'],
        ]);

        $location = SeminarLocation::create($validated);
        $location->loadCount('seminars');

        return response()->json([
            'message' => 'Local criado com sucesso',
            'data' => new AdminLocationResource($location),
        ], 201);
    }

    public function update(Request $request, SeminarLocation $location): JsonResponse
    {
        Gate::authorize('update', $location);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'max_vacancies' => ['sometimes', 'integer', 'min:1'],
        ]);

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
