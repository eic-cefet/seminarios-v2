<?php

namespace App\Http\Controllers\External;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalWorkshopIndexRequest;
use App\Http\Requests\External\ExternalWorkshopStoreRequest;
use App\Http\Requests\External\ExternalWorkshopUpdateRequest;
use App\Http\Resources\External\ExternalResourceCollection;
use App\Http\Resources\External\ExternalWorkshopResource;
use App\Models\Workshop;
use App\Services\SlugService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ExternalWorkshopController extends Controller
{
    use EscapesLikeWildcards;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    #[QueryParameter('search', description: 'Search workshops by name', type: 'string', example: 'Machine Learning')]
    #[QueryParameter('updated_since', description: 'Only return workshops updated on or after this date (ISO 8601)', type: 'string', example: '2026-04-01T00:00:00Z')]
    #[QueryParameter('sort', description: 'Comma-separated sort columns. Prefix with `-` for descending. Allowed: name, updated_at', type: 'string', example: '-name')]
    public function index(ExternalWorkshopIndexRequest $request): ExternalResourceCollection
    {
        $validated = $request->validated();

        $query = Workshop::withCount('seminars');

        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $pairs = $request->sortPairs();
        if ($pairs === []) {
            $query->orderBy('name');
        } else {
            foreach ($pairs as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }
        }

        $workshops = $query->paginate(15);

        $lastModified = collect($workshops->items())->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return new ExternalResourceCollection($workshops, ExternalWorkshopResource::class);
    }

    public function show(Request $request, Workshop $workshop): ExternalWorkshopResource
    {
        Gate::authorize('view', $workshop);

        $workshop->loadCount('seminars');

        $request->attributes->set('external_last_modified', $workshop->updated_at);

        return new ExternalWorkshopResource($workshop);
    }

    #[BodyParameter('name', description: 'Workshop name (must be unique; the slug is derived from this value)', type: 'string', example: 'Distributed Systems Lab')]
    #[BodyParameter('description', description: 'Markdown description of the workshop', type: 'string', example: 'Lab focused on distributed systems research and applications.')]
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

    #[BodyParameter('name', description: 'Workshop name (must be unique excluding the current record; the slug is regenerated when this changes)', type: 'string', example: 'Distributed Systems Lab')]
    #[BodyParameter('description', description: 'Markdown description of the workshop', type: 'string', example: 'Lab focused on distributed systems research and applications.')]
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

    public function destroy(Workshop $workshop): JsonResponse
    {
        Gate::authorize('delete', $workshop);

        DB::transaction(function () use ($workshop) {
            if ($workshop->seminars()->lockForUpdate()->exists()) {
                throw ApiException::workshopInUse();
            }

            $workshop->delete();
        });

        return response()->json(['message' => 'Workshop deleted successfully.']);
    }
}
