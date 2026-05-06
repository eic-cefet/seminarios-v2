<?php

namespace App\Http\Controllers\External;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarTypeStoreRequest;
use App\Http\Requests\External\ExternalSeminarTypeUpdateRequest;
use App\Http\Resources\External\ExternalSeminarTypeResource;
use App\Models\SeminarType;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExternalSeminarTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SeminarType::class);

        $types = SeminarType::orderBy('name')->get();

        $lastModified = $types->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return ExternalSeminarTypeResource::collection($types)
            ->additional(['meta' => ['total' => $types->count()]]);
    }

    public function show(Request $request, SeminarType $seminarType): ExternalSeminarTypeResource
    {
        Gate::authorize('view', $seminarType);

        $request->attributes->set('external_last_modified', $seminarType->updated_at);

        return new ExternalSeminarTypeResource($seminarType);
    }

    #[BodyParameter('name', description: 'Seminar type display name (must be unique)', type: 'string', example: 'Qualificação')]
    #[BodyParameter('name_plural', description: 'Plural form of the type display name (optional)', type: 'string', example: 'Qualificações')]
    #[BodyParameter('gender', description: 'Grammatical gender (defaults to masculine)', type: 'string', example: 'feminine')]
    public function store(ExternalSeminarTypeStoreRequest $request): JsonResponse
    {
        $type = SeminarType::create($request->validated());

        return response()->json([
            'message' => 'Seminar type created successfully.',
            'data' => (new ExternalSeminarTypeResource($type))->resolve($request),
        ], 201);
    }

    #[BodyParameter('name', description: 'Seminar type display name (must be unique, excluding the current record)', type: 'string', example: 'Qualificação')]
    #[BodyParameter('name_plural', description: 'Plural form of the type display name (optional)', type: 'string', example: 'Qualificações')]
    #[BodyParameter('gender', description: 'Grammatical gender (defaults to masculine)', type: 'string', example: 'feminine')]
    public function update(ExternalSeminarTypeUpdateRequest $request, SeminarType $seminarType): JsonResponse
    {
        $seminarType->update($request->validated());

        return response()->json([
            'message' => 'Seminar type updated successfully.',
            'data' => (new ExternalSeminarTypeResource($seminarType))->resolve($request),
        ]);
    }

    public function destroy(SeminarType $seminarType): JsonResponse
    {
        Gate::authorize('delete', $seminarType);

        DB::transaction(function () use ($seminarType) {
            if ($seminarType->seminars()->lockForUpdate()->exists()) {
                throw ApiException::seminarTypeInUse();
            }

            $seminarType->delete();
        });

        return response()->json(['message' => 'Seminar type deleted successfully.']);
    }
}
