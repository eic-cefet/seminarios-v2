<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeminarStoreRequest;
use App\Http\Requests\Admin\SeminarUpdateRequest;
use App\Http\Resources\Admin\AdminSeminarResource;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\Workshop;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminSeminarController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Seminar::class);

        $user = $request->user();
        $query = Seminar::with([
            'seminarType',
            'seminarLocation',
            'workshop',
            'creator',
            'subjects',
            'speakers',
        ])->withCount('registrations');

        // Teachers only see their own seminars
        if ($user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $query->where('created_by', $user->id);
        }

        // Search by name
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Filter upcoming seminars only
        if ($request->boolean('upcoming')) {
            $query->where('scheduled_at', '>=', now());
        }

        $seminars = $query->orderByDesc('scheduled_at')->paginate(15);

        return AdminSeminarResource::collection($seminars);
    }

    public function show(Seminar $seminar): AdminSeminarResource
    {
        Gate::authorize('view', $seminar);

        $seminar->load([
            'seminarType',
            'seminarLocation',
            'workshop',
            'creator',
            'subjects',
            'speakers.speakerData',
        ])->loadCount('registrations');

        return new AdminSeminarResource($seminar);
    }

    public function store(SeminarStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $seminar = DB::transaction(function () use ($validated, $request) {
            // Auto-generate slug from name
            $slug = $this->slugService->generateUnique($validated['name'], Seminar::class);

            // Create seminar
            $seminar = Seminar::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'room_link' => $validated['room_link'] ?? null,
                'active' => $validated['active'],
                'seminar_location_id' => $validated['seminar_location_id'],
                'seminar_type_id' => $validated['seminar_type_id'] ?? null,
                'workshop_id' => $validated['workshop_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Handle subject auto-creation and attachment
            $subjectIds = [];
            foreach ($validated['subject_names'] as $subjectName) {
                $subject = Subject::firstOrCreate(['name' => trim($subjectName)]);
                $subjectIds[] = $subject->id;
            }
            $seminar->subjects()->sync($subjectIds);

            // Attach speakers
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
            'message' => 'Seminário criado com sucesso',
            'data' => new AdminSeminarResource($seminar),
        ], 201);
    }

    public function update(SeminarUpdateRequest $request, Seminar $seminar): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $seminar) {
            // Update basic fields
            if (isset($validated['name'])) {
                $seminar->name = $validated['name'];
                // Regenerate slug if name changed
                $seminar->slug = $this->slugService->generateUnique($validated['name'], Seminar::class, 'slug', $seminar->id);
            }
            if (array_key_exists('description', $validated)) {
                $seminar->description = $validated['description'];
            }
            if (isset($validated['scheduled_at'])) {
                $seminar->scheduled_at = $validated['scheduled_at'];
            }
            if (array_key_exists('room_link', $validated)) {
                $seminar->room_link = $validated['room_link'];
            }
            if (isset($validated['active'])) {
                $seminar->active = $validated['active'];
            }
            if (isset($validated['seminar_location_id'])) {
                $seminar->seminar_location_id = $validated['seminar_location_id'];
            }
            if (array_key_exists('seminar_type_id', $validated)) {
                $seminar->seminar_type_id = $validated['seminar_type_id'];
            }
            if (array_key_exists('workshop_id', $validated)) {
                $seminar->workshop_id = $validated['workshop_id'];
            }

            $seminar->save();

            // Handle subject auto-creation and syncing
            if (isset($validated['subject_names'])) {
                $subjectIds = [];
                foreach ($validated['subject_names'] as $subjectName) {
                    $subject = Subject::firstOrCreate(['name' => trim($subjectName)]);
                    $subjectIds[] = $subject->id;
                }
                $seminar->subjects()->sync($subjectIds);
            }

            // Sync speakers
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

        return response()->json([
            'message' => 'Seminário atualizado com sucesso',
            'data' => new AdminSeminarResource($seminar),
        ]);
    }

    public function destroy(Seminar $seminar): JsonResponse
    {
        Gate::authorize('delete', $seminar);

        // Soft delete
        $seminar->delete();

        return response()->json([
            'message' => 'Seminário excluído com sucesso',
        ]);
    }

    /**
     * Get all seminar types for dropdown
     */
    public function listTypes(): JsonResponse
    {
        $types = SeminarType::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $types,
        ]);
    }

    /**
     * Get all workshops for dropdown
     */
    public function listWorkshops(): JsonResponse
    {
        $workshops = Workshop::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $workshops,
        ]);
    }

    /**
     * Get all locations without pagination for dropdown
     */
    public function listLocations(): JsonResponse
    {
        $locations = SeminarLocation::orderBy('name')->get(['id', 'name', 'max_vacancies']);

        return response()->json([
            'data' => $locations,
        ]);
    }
}
