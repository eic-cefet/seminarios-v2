<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\SeminarLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalLocationController extends Controller
{
    public function index(): JsonResponse
    {
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
        return response()->json([
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:seminar_locations,name'],
            'max_vacancies' => ['required', 'integer', 'min:1'],
        ]);

        $location = SeminarLocation::create($validated);

        return response()->json([
            'message' => 'Location created successfully.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'max_vacancies' => $location->max_vacancies,
            ],
        ], 201);
    }

    public function update(Request $request, SeminarLocation $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:seminar_locations,name,'.$location->id],
            'max_vacancies' => ['sometimes', 'integer', 'min:1'],
        ]);

        $location->update($validated);

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
