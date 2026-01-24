<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminWorkshopResource;
use App\Models\Seminar;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminWorkshopController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Workshop::class);

        $query = Workshop::withCount('seminars');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $workshops = $query->orderBy('name')->paginate(15);

        return AdminWorkshopResource::collection($workshops);
    }

    public function show(Workshop $workshop): AdminWorkshopResource
    {
        Gate::authorize('view', $workshop);

        $workshop->loadCount('seminars');
        $workshop->load('seminars:id,name,slug,scheduled_at,workshop_id');

        return new AdminWorkshopResource($workshop);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Workshop::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:workshops,name'],
            'description' => ['nullable', 'string'],
            'seminar_ids' => ['nullable', 'array'],
            'seminar_ids.*' => ['integer', 'exists:seminars,id'],
        ]);

        $workshop = Workshop::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['seminar_ids'])) {
            Seminar::whereIn('id', $validated['seminar_ids'])
                ->update(['workshop_id' => $workshop->id]);
        }

        $workshop->loadCount('seminars');

        return response()->json([
            'message' => 'Workshop criado com sucesso',
            'data' => new AdminWorkshopResource($workshop),
        ], 201);
    }

    public function update(Request $request, Workshop $workshop): JsonResponse
    {
        Gate::authorize('update', $workshop);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:workshops,name,'.$workshop->id],
            'description' => ['nullable', 'string'],
            'seminar_ids' => ['nullable', 'array'],
            'seminar_ids.*' => ['integer', 'exists:seminars,id'],
        ]);

        $workshop->update([
            'name' => $validated['name'],
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

        return response()->json([
            'message' => 'Workshop atualizado com sucesso',
            'data' => new AdminWorkshopResource($workshop),
        ]);
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
            $query->where('name', 'like', "%{$search}%");
        }

        $seminars = $query->orderByDesc('scheduled_at')->limit(20)->get();

        return response()->json(['data' => $seminars]);
    }
}
