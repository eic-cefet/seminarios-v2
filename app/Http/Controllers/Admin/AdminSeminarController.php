<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Concerns\ResolvesSubjects;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeminarStoreRequest;
use App\Http\Requests\Admin\SeminarUpdateRequest;
use App\Http\Resources\Admin\AdminSeminarResource;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Workshop;
use App\Services\SeminarQueryService;
use App\Services\SeminarVisibilityService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminSeminarController extends Controller
{
    use EscapesLikeWildcards, ResolvesSubjects;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function index(Request $request, SeminarQueryService $seminars, SeminarVisibilityService $visibility): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Seminar::class);

        $query = $seminars->forList(Seminar::query())->with(['workshop', 'creator']);
        $query = $visibility->visibleSeminars($query, $request->user());

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->boolean('upcoming')) {
            $query->where('scheduled_at', '>=', now());
        }

        $seminars = $query->orderByDesc('scheduled_at')->paginate(15);

        return AdminSeminarResource::collection($seminars);
    }

    public function show(Seminar $seminar): AdminSeminarResource
    {
        Gate::authorize('view', $seminar);

        $seminar->load([...SeminarQueryService::DETAIL_RELATIONS, 'creator'])
            ->loadCount('registrations');

        return new AdminSeminarResource($seminar);
    }

    public function store(SeminarStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $seminar = DB::transaction(function () use ($validated, $request) {
            $slug = $this->slugService->generateUnique($validated['name'], Seminar::class);

            $seminar = Seminar::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'duration_minutes' => $validated['duration_minutes'],
                'room_link' => $validated['room_link'] ?? null,
                'active' => $validated['active'],
                'seminar_location_id' => $validated['seminar_location_id'],
                'seminar_type_id' => $validated['seminar_type_id'] ?? null,
                'workshop_id' => $validated['workshop_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $seminar->subjects()->sync($this->resolveSubjectNames($validated['subject_names']));
            $seminar->speakers()->sync($validated['speaker_ids']);

            return $seminar;
        });

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'creator',
            'subjects',
            'speakers',
        ])->loadCount('registrations');

        return response()->json([
            'message' => 'Apresentação criada com sucesso',
            'data' => new AdminSeminarResource($seminar),
        ], 201);
    }

    public function update(SeminarUpdateRequest $request, Seminar $seminar): JsonResponse
    {
        $validated = $request->validated();
        $oldScheduledAt = $seminar->scheduled_at?->copy();

        DB::transaction(function () use ($validated, $seminar) {
            $fields = Arr::only($validated, [
                'name', 'description', 'scheduled_at', 'duration_minutes', 'room_link',
                'active', 'seminar_location_id', 'seminar_type_id', 'workshop_id',
            ]);

            if (isset($fields['name'])) {
                $fields['slug'] = $this->slugService->generateUnique(
                    $fields['name'], Seminar::class, 'slug', $seminar->id
                );
            }

            if (! empty($fields)) {
                $seminar->fill($fields)->save();
            }

            if (isset($validated['subject_names'])) {
                $seminar->subjects()->sync($this->resolveSubjectNames($validated['subject_names']));
            }

            if (isset($validated['speaker_ids'])) {
                $seminar->speakers()->sync($validated['speaker_ids']);
            }
        });

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'creator',
            'subjects',
            'speakers',
        ])->loadCount('registrations');

        if ($oldScheduledAt && $seminar->scheduled_at && ! $oldScheduledAt->equalTo($seminar->scheduled_at)) {
            ProcessSeminarRescheduleJob::dispatch($seminar, $oldScheduledAt)->afterCommit();
        }

        return response()->json([
            'message' => 'Apresentação atualizada com sucesso',
            'data' => new AdminSeminarResource($seminar),
        ]);
    }

    public function destroy(Seminar $seminar): JsonResponse
    {
        Gate::authorize('delete', $seminar);

        $seminar->delete();

        return response()->json([
            'message' => 'Apresentação excluída com sucesso',
        ]);
    }

    public function listTypes(): JsonResponse
    {
        $types = SeminarType::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $types,
        ]);
    }

    public function listWorkshops(): JsonResponse
    {
        $workshops = Workshop::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $workshops,
        ]);
    }

    public function listLocations(): JsonResponse
    {
        $locations = SeminarLocation::orderBy('name')->get(['id', 'name', 'max_vacancies']);

        return response()->json([
            'data' => $locations,
        ]);
    }
}
