<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Resources\External\ExternalWorkshopResource;
use App\Models\Workshop;
use App\Services\SlugService;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExternalWorkshopController extends Controller
{
    public function __construct(
        private readonly SlugService $slugService
    ) {}

    #[QueryParameter('search', description: 'Search workshops by name', type: 'string', example: 'Inteligência Artificial')]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $query = Workshop::withCount('seminars');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', '%'.addcslashes($search, '%_').'%');
        }

        $workshops = $query->orderBy('name')->paginate(15);

        return ExternalWorkshopResource::collection($workshops);
    }

    public function show(Workshop $workshop): ExternalWorkshopResource
    {
        Gate::authorize('view', $workshop);

        $workshop->loadCount('seminars');

        return new ExternalWorkshopResource($workshop);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Workshop::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:workshops,name'],
            'description' => ['nullable', 'string'],
        ]);

        $workshop = Workshop::create([
            'name' => $validated['name'],
            'slug' => $this->slugService->generateUnique($validated['name'], Workshop::class),
            'description' => $validated['description'] ?? null,
        ]);

        $workshop->loadCount('seminars');

        return response()->json([
            'message' => 'Workshop created successfully.',
            'data' => new ExternalWorkshopResource($workshop),
        ], 201);
    }

    public function update(Request $request, Workshop $workshop): JsonResponse
    {
        Gate::authorize('update', $workshop);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:workshops,name,'.$workshop->id],
            'description' => ['nullable', 'string'],
        ]);

        $fields = [];

        if (isset($validated['name'])) {
            $fields['name'] = $validated['name'];
            $fields['slug'] = $this->slugService->generateUnique(
                $validated['name'], Workshop::class, 'slug', $workshop->id
            );
        }

        if (array_key_exists('description', $validated)) {
            $fields['description'] = $validated['description'];
        }

        if (! empty($fields)) {
            $workshop->update($fields);
        }

        $workshop->loadCount('seminars');

        return response()->json([
            'message' => 'Workshop updated successfully.',
            'data' => new ExternalWorkshopResource($workshop),
        ]);
    }
}
