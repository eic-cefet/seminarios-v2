<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRegistrationStoreRequest;
use App\Http\Resources\Admin\AdminRegistrationResource;
use App\Mail\SeminarRegistrationConfirmation;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\SeminarVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

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

    public function store(AdminRegistrationStoreRequest $request): JsonResponse
    {
        $seminar = Seminar::findOrFail($request->integer('seminar_id'));

        Gate::authorize('create', [Registration::class, $seminar]);

        $userIds = $request->collect('user_ids')->map(fn ($id) => (int) $id);

        $created = collect();
        $alreadyRegistered = 0;
        $markedPresent = 0;

        DB::transaction(function () use ($seminar, $userIds, $created, &$alreadyRegistered, &$markedPresent): void {
            foreach ($userIds as $userId) {
                $registration = Registration::firstOrCreate(
                    [
                        'seminar_id' => $seminar->id,
                        'user_id' => $userId,
                    ],
                    [
                        'present' => true,
                    ]
                );

                if ($registration->wasRecentlyCreated) {
                    $created->push($registration);

                    continue;
                }

                $alreadyRegistered++;

                if (! $registration->present) {
                    $registration->update(['present' => true]);
                    $markedPresent++;
                }
            }
        });

        $users = User::whereIn('id', $created->pluck('user_id'))->get()->keyBy('id');

        foreach ($created as $registration) {
            $user = $users->get($registration->user_id);
            Mail::to($user)->queue(new SeminarRegistrationConfirmation($user, $seminar));
        }

        AuditLog::record(AuditEvent::RegistrationsAddedByAdmin, auditable: $seminar, eventData: [
            'seminar_id' => $seminar->id,
            'user_ids' => $userIds->all(),
            'created' => $created->count(),
            'already_registered' => $alreadyRegistered,
            'marked_present' => $markedPresent,
        ]);

        return response()->json([
            'message' => 'Inscrições adicionadas com sucesso',
            'data' => [
                'created' => $created->count(),
                'already_registered' => $alreadyRegistered,
                'marked_present' => $markedPresent,
            ],
        ], 201);
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
