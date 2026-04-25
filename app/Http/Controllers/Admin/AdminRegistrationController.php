<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminRegistrationResource;
use App\Models\Registration;
use App\Services\SeminarVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminRegistrationController extends Controller
{
    use EscapesLikeWildcards;

    public function index(Request $request, SeminarVisibilityService $visibility): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Registration::class);

        $query = Registration::with(['user:id,name,email', 'seminar:id,name,slug,scheduled_at,created_by'])
            ->orderByDesc('created_at');

        $query = $visibility->visibleRegistrations($query, $request->user());

        if ($request->filled('seminar_id')) {
            $query->where('seminar_id', $request->input('seminar_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $escaped = $this->escapeLike($search);
            $query->whereHas('user', function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        return AdminRegistrationResource::collection($query->paginate(15));
    }

    public function togglePresence(Registration $registration): JsonResponse
    {
        $registration->loadMissing('seminar');
        Gate::authorize('updatePresence', $registration);

        $registration->present = ! $registration->present;
        $registration->save();

        $registration->load(['user:id,name,email', 'seminar:id,name,slug,scheduled_at']);

        return response()->json([
            'message' => $registration->present ? 'Presença confirmada' : 'Presença removida',
            'data' => new AdminRegistrationResource($registration),
        ]);
    }
}
