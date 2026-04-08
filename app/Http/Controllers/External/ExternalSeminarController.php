<?php

namespace App\Http\Controllers\External;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarStoreRequest;
use App\Http\Requests\External\ExternalSeminarUpdateRequest;
use App\Http\Resources\External\ExternalSeminarResource;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Services\SlugService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExternalSeminarController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

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

    #[BodyParameter('location.name', description: 'Existing locations: Auditório Bloco D, Auditório Bloco E, Laboratório de Informática 1, Sala E-205, Sala E-301. New values are auto-created.', example: 'Sala Remota')]
    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', example: 4)]
    public function store(ExternalSeminarStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $seminar = DB::transaction(function () use ($validated, $request) {
            $location = SeminarLocation::firstOrCreate(
                ['name' => trim($validated['location']['name'])],
                ['max_vacancies' => $validated['location']['max_vacancies'] ?? 50]
            );

            $slug = $this->slugService->generateUnique($validated['name'], Seminar::class);

            $seminar = Seminar::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'room_link' => $validated['room_link'] ?? null,
                'active' => $validated['active'] ?? true,
                'seminar_location_id' => $location->id,
                'seminar_type_id' => $validated['seminar_type_id'] ?? null,
                'workshop_id' => $validated['workshop_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $subjectIds = $this->resolveSubjects($validated['subjects']);
            $seminar->subjects()->sync($subjectIds);

            $speakerIds = $this->resolveSpeakers($validated['speakers']);
            $seminar->speakers()->sync($speakerIds);

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

    #[BodyParameter('location.name', description: 'Existing locations: Auditório Bloco D, Auditório Bloco E, Laboratório de Informática 1, Sala E-205, Sala E-301. New values are auto-created.', example: 'Sala Remota')]
    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', example: 4)]
    public function update(ExternalSeminarUpdateRequest $request, Seminar $seminar): JsonResponse
    {
        $validated = $request->validated();
        $oldScheduledAt = $seminar->scheduled_at?->copy();

        DB::transaction(function () use ($validated, $seminar) {
            $fields = [];

            foreach (['name', 'description', 'scheduled_at', 'room_link', 'active', 'workshop_id'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $fields[$field] = $validated[$field];
                }
            }

            if (isset($validated['location'])) {
                $location = SeminarLocation::firstOrCreate(
                    ['name' => trim($validated['location']['name'])],
                    ['max_vacancies' => $validated['location']['max_vacancies'] ?? 50]
                );
                $fields['seminar_location_id'] = $location->id;
            }

            if (array_key_exists('seminar_type_id', $validated)) {
                $fields['seminar_type_id'] = $validated['seminar_type_id'];
            }

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

            if (isset($validated['speakers'])) {
                $speakerIds = $this->resolveSpeakers($validated['speakers']);
                $seminar->speakers()->sync($speakerIds);
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

    /**
     * @param  array<array{name: string, email: string, institution?: string, description?: string}>  $speakersData
     * @return array<int>
     */
    private function resolveSpeakers(array $speakersData): array
    {
        $ids = [];
        foreach ($speakersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name']]
            );

            if (array_key_exists('institution', $data) || array_key_exists('description', $data)) {
                UserSpeakerData::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'slug' => $user->speakerData?->slug
                            ?? $this->slugService->generateUnique($user->name, UserSpeakerData::class),
                        'institution' => $data['institution'] ?? null,
                        'description' => $data['description'] ?? null,
                    ]
                );
            } elseif (! $user->speakerData) {
                UserSpeakerData::create([
                    'user_id' => $user->id,
                    'slug' => $this->slugService->generateUnique($user->name, UserSpeakerData::class),
                ]);
            }

            $ids[] = $user->id;
        }

        return $ids;
    }
}
