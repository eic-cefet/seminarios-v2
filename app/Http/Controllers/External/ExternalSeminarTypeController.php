<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\SeminarType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalSeminarTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = SeminarType::orderBy('name')
            ->get()
            ->map(fn (SeminarType $type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]);

        return response()->json(['data' => $types]);
    }

    public function show(SeminarType $seminarType): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $seminarType->id,
                'name' => $seminarType->name,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:seminar_types,name'],
        ]);

        $type = SeminarType::create($validated);

        return response()->json([
            'message' => 'Seminar type created successfully.',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
            ],
        ], 201);
    }

    public function update(Request $request, SeminarType $seminarType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:seminar_types,name,'.$seminarType->id],
        ]);

        $seminarType->update($validated);

        return response()->json([
            'message' => 'Seminar type updated successfully.',
            'data' => [
                'id' => $seminarType->id,
                'name' => $seminarType->name,
            ],
        ]);
    }
}
