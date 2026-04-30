<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Concerns\ResolvesSubjects;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalSeminarIndexRequest;
use App\Http\Requests\External\ExternalSeminarStoreRequest;
use App\Http\Requests\External\ExternalSeminarUpdateRequest;
use App\Http\Resources\External\ExternalResourceCollection;
use App\Http\Resources\External\ExternalSeminarResource;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Services\SeminarVisibilityService;
use App\Services\SlugService;
use App\Support\External\IncludesParser;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExternalSeminarController extends Controller
{
    use EscapesLikeWildcards, ResolvesSubjects;

    /**
     * Whitelist mapping client include keys to eager-load paths.
     *
     * @var array<string, string>
     */
    private const INCLUDE_MAP = [
        'workshop' => 'workshop',
        'seminar_type' => 'seminarType',
        'location' => 'seminarLocation',
        'subjects' => 'subjects',
        'speakers' => 'speakers.speakerData',
    ];

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    #[QueryParameter('scheduled_from', description: 'Filter seminars scheduled on or after this date (ISO 8601)', type: 'string', example: '2026-01-01T00:00:00Z')]
    #[QueryParameter('scheduled_to', description: 'Filter seminars scheduled on or before this date (ISO 8601)', type: 'string', example: '2026-12-31T23:59:59Z')]
    #[QueryParameter('upcoming', description: 'Shortcut for scheduled_from=now()', type: 'boolean', example: true)]
    #[QueryParameter('updated_since', description: 'Only return seminars updated on or after this date (ISO 8601)', type: 'string', example: '2026-04-01T00:00:00Z')]
    #[QueryParameter('sort', description: 'Comma-separated sort columns. Prefix with `-` for descending. Allowed: scheduled_at, name, updated_at', type: 'string', example: '-scheduled_at,name')]
    #[QueryParameter('include', description: 'Comma-separated relations to eager-load. Allowed: workshop, seminar_type, location, subjects, speakers.', type: 'string', example: 'workshop,subjects')]
    public function index(ExternalSeminarIndexRequest $request, SeminarVisibilityService $visibility): ExternalResourceCollection
    {
        $validated = $request->validated();

        $query = $visibility->visibleSeminars(Seminar::query(), $request->user());

        $includes = $this->resolveIncludes($request);
        if ($includes !== []) {
            $query->with($includes);
        }

        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        if (array_key_exists('active', $validated)) {
            $query->where('active', (bool) $validated['active']);
        }

        if (! empty($validated['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $validated['scheduled_from']);
        }

        if (! empty($validated['scheduled_to'])) {
            $query->where('scheduled_at', '<=', $validated['scheduled_to']);
        }

        if (! empty($validated['upcoming'])) {
            $query->where('scheduled_at', '>=', now());
        }

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $pairs = $request->sortPairs();
        if ($pairs === []) {
            $query->orderByDesc('scheduled_at');
        } else {
            foreach ($pairs as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }
        }

        $seminars = $query->paginate(15);

        $lastModified = collect($seminars->items())->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return new ExternalResourceCollection($seminars, ExternalSeminarResource::class);
    }

    #[QueryParameter('include', description: 'Comma-separated relations to eager-load. Allowed: workshop, seminar_type, location, subjects, speakers.', type: 'string', example: 'workshop,subjects')]
    public function show(Request $request, Seminar $seminar): ExternalSeminarResource
    {
        Gate::authorize('view', $seminar);

        $includes = $this->resolveIncludes($request);
        if ($includes !== []) {
            $seminar->load($includes);
        }

        $request->attributes->set('external_last_modified', $seminar->updated_at);

        return new ExternalSeminarResource($seminar);
    }

    #[BodyParameter('name', description: 'Seminar title (the slug is derived from this value)', type: 'string', example: 'Defesa de TCC — Sistemas Distribuídos')]
    #[BodyParameter('description', description: 'Seminar description (Markdown supported)', type: 'string', example: 'Apresentação sobre arquiteturas event-driven aplicadas a microserviços.')]
    #[BodyParameter('scheduled_at', description: 'ISO-8601 datetime when the seminar will take place', type: 'string', example: '2026-06-15T14:00:00Z')]
    #[BodyParameter('room_link', description: 'Optional URL to the virtual meeting room', type: 'string', example: 'https://meet.google.com/abc-defg-hij')]
    #[BodyParameter('active', description: 'Whether the seminar is published/visible (defaults to true)', type: 'boolean', example: true)]
    #[BodyParameter('seminar_location_id', description: 'ID of the physical/virtual seminar location', type: 'integer', example: 1)]
    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', type: 'integer', example: 4)]
    #[BodyParameter('workshop_id', description: 'Optional ID of the workshop this seminar belongs to', type: 'integer', example: 2)]
    #[BodyParameter('subjects', description: 'List of subject names; existing subjects are reused, new ones are created on the fly', type: 'array', example: ['Sistemas Distribuídos', 'Microserviços'])]
    #[BodyParameter('speaker_ids', description: 'List of user IDs to assign as speakers (must exist in users table)', type: 'array', example: [12, 34])]
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

            $seminar->subjects()->sync($this->resolveSubjectNames($validated['subjects']));
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

    #[BodyParameter('name', description: 'Seminar title (the slug is regenerated when this changes)', type: 'string', example: 'Defesa de TCC — Sistemas Distribuídos')]
    #[BodyParameter('description', description: 'Seminar description (Markdown supported)', type: 'string', example: 'Apresentação sobre arquiteturas event-driven aplicadas a microserviços.')]
    #[BodyParameter('scheduled_at', description: 'ISO-8601 datetime when the seminar will take place; changing this dispatches reschedule notifications', type: 'string', example: '2026-06-15T14:00:00Z')]
    #[BodyParameter('room_link', description: 'Optional URL to the virtual meeting room', type: 'string', example: 'https://meet.google.com/abc-defg-hij')]
    #[BodyParameter('active', description: 'Whether the seminar is published/visible', type: 'boolean', example: true)]
    #[BodyParameter('seminar_location_id', description: 'ID of the physical/virtual seminar location', type: 'integer', example: 1)]
    #[BodyParameter('seminar_type_id', description: 'ID of the seminar type. Available: 1=Seminário, 2=Qualificação, 3=Dissertação, 4=TCC, 5=Aula inaugural, 6=Painel, 7=Doutorado', type: 'integer', example: 4)]
    #[BodyParameter('workshop_id', description: 'Optional ID of the workshop this seminar belongs to', type: 'integer', example: 2)]
    #[BodyParameter('subjects', description: 'List of subject names to fully replace the seminar subject set; existing subjects are reused, new ones are created on the fly', type: 'array', example: ['Sistemas Distribuídos', 'Microserviços'])]
    #[BodyParameter('speaker_ids', description: 'List of user IDs to fully replace the seminar speaker set (must exist in users table)', type: 'array', example: [12, 34])]
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
                $seminar->subjects()->sync($this->resolveSubjectNames($validated['subjects']));
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
     * Resolve the `include` query parameter into eager-load paths.
     *
     * @return list<string>
     */
    private function resolveIncludes(Request $request): array
    {
        try {
            return IncludesParser::resolve($request->query('include'), self::INCLUDE_MAP);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['include' => $e->getMessage()]);
        }
    }
}
