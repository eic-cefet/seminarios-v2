<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminSeminarResource;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AdminSeminarController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Seminar::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'link' => ['nullable', 'url', 'max:500'],
            'active' => ['required', 'boolean'],
            'seminar_location_id' => ['required', 'integer', 'exists:seminar_locations,id'],
            'seminar_type_id' => ['nullable', 'integer', 'exists:seminar_types,id'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],
            'subject_names' => ['required', 'array', 'min:1'],
            'subject_names.*' => ['required', 'string', 'max:255'],
            'speaker_ids' => ['required', 'array', 'min:1'],
            'speaker_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $seminar = DB::transaction(function () use ($validated, $request) {
            // Auto-generate slug from name
            $slug = $this->generateUniqueSlug($validated['name']);

            // Create seminar
            $seminar = Seminar::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'link' => $validated['link'] ?? null,
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

    public function update(Request $request, Seminar $seminar): JsonResponse
    {
        Gate::authorize('update', $seminar);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'date'],
            'link' => ['nullable', 'url', 'max:500'],
            'active' => ['sometimes', 'boolean'],
            'seminar_location_id' => ['sometimes', 'integer', 'exists:seminar_locations,id'],
            'seminar_type_id' => ['nullable', 'integer', 'exists:seminar_types,id'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],
            'subject_names' => ['sometimes', 'array', 'min:1'],
            'subject_names.*' => ['required', 'string', 'max:255'],
            'speaker_ids' => ['sometimes', 'array', 'min:1'],
            'speaker_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $seminar) {
            // Update basic fields
            if (isset($validated['name'])) {
                $seminar->name = $validated['name'];
                // Regenerate slug if name changed
                $seminar->slug = $this->generateUniqueSlug($validated['name'], $seminar->id);
            }
            if (array_key_exists('description', $validated)) {
                $seminar->description = $validated['description'];
            }
            if (isset($validated['scheduled_at'])) {
                $seminar->scheduled_at = $validated['scheduled_at'];
            }
            if (array_key_exists('link', $validated)) {
                $seminar->link = $validated['link'];
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

    /**
     * Generate a unique slug from name
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = Seminar::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
            $query = Seminar::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
