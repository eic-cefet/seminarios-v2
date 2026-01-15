<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminRegistrationResource;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminRegistrationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Registration::class);

        $query = Registration::with(['user:id,name,email', 'seminar:id,name,slug,scheduled_at'])
            ->orderByDesc('created_at');

        if ($request->filled('seminar_id')) {
            $query->where('seminar_id', $request->input('seminar_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return AdminRegistrationResource::collection($query->paginate(15));
    }

    public function togglePresence(Registration $registration): JsonResponse
    {
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
