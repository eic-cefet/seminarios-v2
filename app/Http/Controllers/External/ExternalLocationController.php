<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalLocationStoreRequest;
use App\Http\Requests\External\ExternalLocationUpdateRequest;
use App\Models\SeminarLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ExternalLocationController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', SeminarLocation::class);

        $locations = SeminarLocation::orderBy('name')
            ->get()
            ->map(fn (SeminarLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ]);

        return response()->json(['data' => $locations]);
    }

    public function show(SeminarLocation $location): JsonResponse
    {
        Gate::authorize('view', $location);

        return response()->json([
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ],
        ]);
    }

    public function store(ExternalLocationStoreRequest $request): JsonResponse
    {
        $location = SeminarLocation::create($request->validated());

        return response()->json([
            'message' => 'Location created successfully.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ],
        ], 201);
    }

    public function update(ExternalLocationUpdateRequest $request, SeminarLocation $location): JsonResponse
    {
        $location->update($request->validated());

        return response()->json([
            'message' => 'Location updated successfully.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ],
        ]);
    }
}
