<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminSubjectResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminSubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Subject::class);

        $query = Subject::withCount('seminars');

        // Search by name
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $subjects = $query->orderBy('name')->paginate(15);

        return AdminSubjectResource::collection($subjects);
    }

    public function show(Subject $subject): AdminSubjectResource
    {
        Gate::authorize('view', $subject);

        $subject->loadCount('seminars');

        return new AdminSubjectResource($subject);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Subject::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name'],
        ]);

        $subject = Subject::create($validated);
        $subject->loadCount('seminars');

        return response()->json([
            'message' => 'Tópico criado com sucesso',
            'data' => new AdminSubjectResource($subject),
        ], 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        Gate::authorize('update', $subject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name,'.$subject->id],
        ]);

        $subject->update($validated);
        $subject->loadCount('seminars');

        return response()->json([
            'message' => 'Tópico atualizado com sucesso',
            'data' => new AdminSubjectResource($subject),
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        Gate::authorize('delete', $subject);

        // Check if subject has seminars
        if ($subject->seminars()->exists()) {
            throw ApiException::subjectInUse();
        }

        $subject->delete();

        return response()->json([
            'message' => 'Tópico excluído com sucesso',
        ]);
    }

    public function merge(Request $request): JsonResponse
    {
        Gate::authorize('merge', Subject::class);

        $validated = $request->validate([
            'target_id' => ['required', 'integer', 'exists:subjects,id'],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['required', 'integer', 'exists:subjects,id', 'different:target_id'],
            'new_name' => ['nullable', 'string', 'max:255'],
        ]);

        $targetId = $validated['target_id'];
        // Validation rule 'different:target_id' already ensures no source_id equals target_id
        $sourceIds = $validated['source_ids'];

        DB::beginTransaction();
        try {
            $target = Subject::findOrFail($targetId);

            // Update name if provided
            if (! empty($validated['new_name'])) {
                $target->name = $validated['new_name'];
                $target->save();
            }

            // Get all seminar IDs associated with source subjects
            $seminarIds = DB::table('seminar_subject')
                ->whereIn('subject_id', $sourceIds)
                ->pluck('seminar_id')
                ->unique();

            // Batch insert target subject for all affected seminars (ignoring duplicates)
            $insertData = $seminarIds->map(fn ($seminarId) => [
                'seminar_id' => $seminarId,
                'subject_id' => $targetId,
            ])->toArray();

            if (! empty($insertData)) {
                DB::table('seminar_subject')->insertOrIgnore($insertData);
            }

            // Remove all source subject associations
            DB::table('seminar_subject')
                ->whereIn('subject_id', $sourceIds)
                ->delete();

            // Delete source subjects
            Subject::whereIn('id', $sourceIds)->delete();

            DB::commit();

            $target->loadCount('seminars');

            return response()->json([
                'message' => 'Tópicos mesclados com sucesso',
                'data' => new AdminSubjectResource($target),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw ApiException::cannotMergeSubjects();
        }
    }
}
