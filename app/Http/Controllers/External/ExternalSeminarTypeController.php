<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarTypeStoreRequest;
use App\Http\Requests\External\ExternalSeminarTypeUpdateRequest;
use App\Models\SeminarType;
use Illuminate\Http\JsonResponse;

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

    public function store(ExternalSeminarTypeStoreRequest $request): JsonResponse
    {
        $type = SeminarType::create($request->validated());

        return response()->json([
            'message' => 'Seminar type created successfully.',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
            ],
        ], 201);
    }

    public function update(ExternalSeminarTypeUpdateRequest $request, SeminarType $seminarType): JsonResponse
    {
        $seminarType->update($request->validated());

        return response()->json([
            'message' => 'Seminar type updated successfully.',
            'data' => [
                'id' => $seminarType->id,
                'name' => $seminarType->name,
            ],
        ]);
    }
}
