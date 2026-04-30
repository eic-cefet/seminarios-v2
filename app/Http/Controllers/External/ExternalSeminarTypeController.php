<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarTypeStoreRequest;
use App\Http\Requests\External\ExternalSeminarTypeUpdateRequest;
use App\Http\Resources\External\ExternalSeminarTypeResource;
use App\Models\SeminarType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExternalSeminarTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SeminarType::class);

        $types = SeminarType::orderBy('name')->get();

        $lastModified = $types->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return ExternalSeminarTypeResource::collection($types);
    }

    public function show(Request $request, SeminarType $seminarType): ExternalSeminarTypeResource
    {
        Gate::authorize('view', $seminarType);

        $request->attributes->set('external_last_modified', $seminarType->updated_at);

        return new ExternalSeminarTypeResource($seminarType);
    }

    public function store(ExternalSeminarTypeStoreRequest $request): JsonResponse
    {
        $type = SeminarType::create($request->validated());

        return response()->json([
            'message' => 'Seminar type created successfully.',
            'data' => (new ExternalSeminarTypeResource($type))->resolve($request),
        ], 201);
    }

    public function update(ExternalSeminarTypeUpdateRequest $request, SeminarType $seminarType): JsonResponse
    {
        $seminarType->update($request->validated());

        return response()->json([
            'message' => 'Seminar type updated successfully.',
            'data' => (new ExternalSeminarTypeResource($seminarType))->resolve($request),
        ]);
    }
}
