<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminWorkshopStoreRequest;
use App\Http\Requests\Admin\AdminWorkshopUpdateRequest;
use App\Http\Resources\Admin\AdminWorkshopResource;
use App\Models\Seminar;
use App\Models\Workshop;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminWorkshopController extends Controller
{
    use EscapesLikeWildcards;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $query = Workshop::withCount('seminars');

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        $workshops = $query->orderBy('name')->paginate(15);

        return AdminWorkshopResource::collection($workshops);
    }

    public function show(Workshop $workshop): AdminWorkshopResource
    {
        Gate::authorize('view', $workshop);

        $workshop->loadCount('seminars')
            ->load('seminars:id,name,slug,scheduled_at,workshop_id');

        return new AdminWorkshopResource($workshop);
    }

    public function store(AdminWorkshopStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $slug = $this->slugService->generateUnique($validated['name'], Workshop::class);

        $workshop = Workshop::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['seminar_ids'])) {
            Seminar::whereIn('id', $validated['seminar_ids'])
                ->update(['workshop_id' => $workshop->id]);
        }

        $workshop->loadCount('seminars');

        return (new AdminWorkshopResource($workshop))
            ->additional(['message' => 'Workshop criado com sucesso'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(AdminWorkshopUpdateRequest $request, Workshop $workshop): JsonResponse
    {
        $validated = $request->validated();

        $slug = $this->slugService->generateUnique(
            $validated['name'], Workshop::class, 'slug', $workshop->id
        );

        $workshop->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if (array_key_exists('seminar_ids', $validated)) {
            Seminar::where('workshop_id', $workshop->id)
                ->update(['workshop_id' => null]);

            if (! empty($validated['seminar_ids'])) {
                Seminar::whereIn('id', $validated['seminar_ids'])
                    ->update(['workshop_id' => $workshop->id]);
            }
        }

        $workshop->loadCount('seminars');

        return (new AdminWorkshopResource($workshop))
            ->additional(['message' => 'Workshop atualizado com sucesso'])
            ->response();
    }

    public function destroy(Workshop $workshop): JsonResponse
    {
        Gate::authorize('delete', $workshop);

        if ($workshop->seminars()->exists()) {
            throw ApiException::workshopInUse();
        }

        $workshop->delete();

        return response()->json([
            'message' => 'Workshop excluído com sucesso',
        ]);
    }

    public function searchSeminars(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $workshopId = $request->integer('workshop_id');

        $query = Seminar::query()
            ->select('id', 'name', 'slug', 'scheduled_at', 'workshop_id')
            ->where(function ($q) use ($workshopId) {
                $q->whereNull('workshop_id');
                if ($workshopId) {
                    $q->orWhere('workshop_id', $workshopId);
                }
            });

        if ($search) {
            $escaped = $this->escapeLike($search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        $seminars = $query->orderByDesc('scheduled_at')->limit(20)->get();

        return response()->json(['data' => $seminars]);
    }
}
