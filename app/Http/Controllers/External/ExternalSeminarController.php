<?php

namespace App\Http\Controllers\External;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarStoreRequest;
use App\Http\Requests\External\ExternalSeminarUpdateRequest;
use App\Http\Resources\External\ExternalSeminarResource;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Models\Subject;
use App\Services\SlugService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExternalSeminarController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

    #[QueryParameter('search', description: 'Search seminars by name', type: 'string', example: 'Machine Learning')]
    #[QueryParameter('active', description: 'Filter by active status', type: 'boolean', example: true)]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Seminar::class);

        $query = Seminar::with([
            'seminarType',
            'seminarLocation',
            'workshop',
            'subjects',
            'speakers.speakerData',
        ]);

        $user = $request->user();
        if ($user->hasRole(Role::Teacher) && ! $user->hasRole(Role::Admin)) {
            $query->where('created_by', $user->id);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', '%'.addcslashes($search, '%_').'%');
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $seminars = $query->orderByDesc('scheduled_at')->paginate(15);

        return ExternalSeminarResource::collection($seminars);
    }

    public function show(Seminar $seminar): ExternalSeminarResource
    {
        Gate::authorize('view', $seminar);

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'subjects',
            'speakers.speakerData',
        ]);

        return new ExternalSeminarResource($seminar);
    }

    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', example: 4)]
    public function store(ExternalSeminarStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $seminar = DB::transaction(function () use ($validated, $request) {
            $slug = $this->slugService->generateUnique($validated['name'], Seminar::class);

            $seminar = Seminar::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'room_link' => $validated['room_link'] ?? null,
                'active' => $validated['active'] ?? true,
                'seminar_location_id' => $validated['seminar_location_id'],
                'seminar_type_id' => $validated['seminar_type_id'] ?? null,
                'workshop_id' => $validated['workshop_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $subjectIds = $this->resolveSubjects($validated['subjects']);
            $seminar->subjects()->sync($subjectIds);

            $seminar->speakers()->sync($validated['speaker_ids']);

            return $seminar;
        });

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'subjects',
            'speakers.speakerData',
        ]);

        return response()->json([
            'message' => 'Seminar created successfully.',
            'data' => new ExternalSeminarResource($seminar),
        ], 201);
    }

    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', example: 4)]
    public function update(ExternalSeminarUpdateRequest $request, Seminar $seminar): JsonResponse
    {
        $validated = $request->validated();
        $oldScheduledAt = $seminar->scheduled_at?->copy();

        DB::transaction(function () use ($validated, $seminar) {
            $fields = Arr::only($validated, [
                'name', 'description', 'scheduled_at', 'room_link',
                'active', 'workshop_id', 'seminar_location_id', 'seminar_type_id',
            ]);

            if (isset($fields['name'])) {
                $fields['slug'] = $this->slugService->generateUnique(
                    $fields['name'], Seminar::class, 'slug', $seminar->id
                );
            }

            if (! empty($fields)) {
                $seminar->fill($fields)->save();
            }

            if (isset($validated['subjects'])) {
                $subjectIds = $this->resolveSubjects($validated['subjects']);
                $seminar->subjects()->sync($subjectIds);
            }

            if (isset($validated['speaker_ids'])) {
                $seminar->speakers()->sync($validated['speaker_ids']);
            }
        });

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'subjects',
            'speakers.speakerData',
        ]);

        if ($oldScheduledAt && $seminar->scheduled_at && ! $oldScheduledAt->equalTo($seminar->scheduled_at)) {
            ProcessSeminarRescheduleJob::dispatch($seminar, $oldScheduledAt)->afterCommit();
        }

        return response()->json([
            'message' => 'Seminar updated successfully.',
            'data' => new ExternalSeminarResource($seminar),
        ]);
    }

    /**
     * @param  array<string>  $subjectNames
     * @return array<int>
     */
    private function resolveSubjects(array $subjectNames): array
    {
        $ids = [];
        foreach ($subjectNames as $name) {
            $subject = Subject::firstOrCreate(['name' => trim($name)]);
            $ids[] = $subject->id;
        }

        return $ids;
    }
}
