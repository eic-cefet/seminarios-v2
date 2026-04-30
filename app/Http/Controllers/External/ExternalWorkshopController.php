<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalWorkshopStoreRequest;
use App\Http\Requests\External\ExternalWorkshopUpdateRequest;
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
    use EscapesLikeWildcards;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    #[QueryParameter('search', description: 'Search workshops by name', type: 'string', example: 'Machine Learning')]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $query = Workshop::withCount('seminars');

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
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

    public function store(ExternalWorkshopStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $slug = $this->slugService->generateUnique($validated['name'], Workshop::class);

        $workshop = Workshop::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        $workshop->loadCount('seminars');

        return response()->json([
            'message' => 'Workshop created successfully.',
            'data' => new ExternalWorkshopResource($workshop),
        ], 201);
    }

    public function update(ExternalWorkshopUpdateRequest $request, Workshop $workshop): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $validated['slug'] = $this->slugService->generateUnique(
                $validated['name'], Workshop::class, 'slug', $workshop->id
            );
        }

        $workshop->fill($validated)->save();
        $workshop->loadCount('seminars');

        return response()->json([
            'message' => 'Workshop updated successfully.',
            'data' => new ExternalWorkshopResource($workshop),
        ]);
    }
}
