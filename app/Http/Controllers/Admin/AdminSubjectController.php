<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminSubjectMergeRequest;
use App\Http\Requests\Admin\AdminSubjectStoreRequest;
use App\Http\Requests\Admin\AdminSubjectUpdateRequest;
use App\Http\Resources\Admin\AdminSubjectResource;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AdminSubjectController extends Controller
{
    use EscapesLikeWildcards;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Subject::class);

        $query = Subject::withCount('seminars');

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
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

    public function store(AdminSubjectStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $slug = $this->slugService->generateUnique($validated['name'], Subject::class);

        $subject = Subject::create([...$validated, 'slug' => $slug]);
        $subject->loadCount('seminars');

        return response()->json([
            'message' => 'Tópico criado com sucesso',
            'data' => new AdminSubjectResource($subject),
        ], 201);
    }

    public function update(AdminSubjectUpdateRequest $request, Subject $subject): JsonResponse
    {
        $validated = $request->validated();

        $slug = $this->slugService->generateUnique(
            $validated['name'], Subject::class, 'slug', $subject->id
        );

        $subject->update([...$validated, 'slug' => $slug]);
        $subject->loadCount('seminars');

        return response()->json([
            'message' => 'Tópico atualizado com sucesso',
            'data' => new AdminSubjectResource($subject),
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        Gate::authorize('delete', $subject);

        if ($subject->seminars()->exists()) {
            throw ApiException::subjectInUse();
        }

        $subject->delete();

        return response()->json([
            'message' => 'Tópico excluído com sucesso',
        ]);
    }

    public function merge(AdminSubjectMergeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $targetId = $validated['target_id'];
        $sourceIds = $validated['source_ids'];

        try {
            $target = DB::transaction(function () use ($targetId, $sourceIds, $validated) {
                $target = Subject::findOrFail($targetId);

                if (! empty($validated['new_name'])) {
                    $target->name = $validated['new_name'];
                    $target->slug = $this->slugService->generateUnique(
                        $validated['new_name'], Subject::class, 'slug', $target->id
                    );
                    $target->save();
                }

                $seminarIds = DB::table('seminar_subject')
                    ->whereIn('subject_id', $sourceIds)
                    ->pluck('seminar_id')
                    ->unique();

                $insertData = $seminarIds->map(fn ($seminarId) => [
                    'seminar_id' => $seminarId,
                    'subject_id' => $targetId,
                ])->toArray();

                if (! empty($insertData)) {
                    DB::table('seminar_subject')->insertOrIgnore($insertData);
                }

                DB::table('seminar_subject')
                    ->whereIn('subject_id', $sourceIds)
                    ->delete();

                Subject::whereIn('id', $sourceIds)->delete();

                // Audit log is intentionally inside the transaction — if audit
                // recording fails, the merge is rolled back to ensure auditability.
                AuditLog::record(AuditEvent::SubjectsMerged, auditable: $target, eventData: [
                    'target_id' => $targetId,
                    'source_ids' => $sourceIds,
                    'new_name' => $validated['new_name'] ?? null,
                    'affected_seminar_ids' => $seminarIds->values()->toArray(),
                ]);

                return $target;
            });
        } catch (\Throwable $e) {
            Log::error('Subject merge failed', [
                'exception' => $e->getMessage(),
                'target_id' => $targetId,
                'source_ids' => $sourceIds,
            ]);
            throw ApiException::cannotMergeSubjects();
        }

        $target->loadCount('seminars');

        return response()->json([
            'message' => 'Tópicos mesclados com sucesso',
            'data' => new AdminSubjectResource($target),
        ]);
    }
}
