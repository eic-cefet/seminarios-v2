<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    use EscapesLikeWildcards;

    public function __construct(
        private readonly SlugService $slugService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['studentData', 'speakerData', 'roles'])
            ->orderByDesc('created_at');

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        if ($role = $request->string('role')->trim()->toString()) {
            if ($role === 'user') {
                // "user" is the UI shorthand for "no privilege" — filter users
                // with no assigned role (Admin and Teacher are the only real roles).
                $query->whereDoesntHave('roles');
            } else {
                $query->role($role);
            }
        }

        return AdminUserResource::collection($query->paginate(15));
    }

    public function show(User $user): AdminUserResource
    {
        Gate::authorize('view', $user);

        $user->load(['studentData', 'speakerData', 'roles']);

        return new AdminUserResource($user);
    }

    public function store(AdminUserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $password = $validated['password'] ?? Str::random(24);

        $user = DB::transaction(function () use ($validated, $password) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);

            if (isset($validated['role']) && $validated['role'] !== 'user') {
                $user->assignRole($validated['role']);
            }

            if (! empty($validated['student_data'])) {
                $user->studentData()->create($validated['student_data']);
            }

            if (! empty($validated['speaker_data'])) {
                $speakerData = $validated['speaker_data'];
                $speakerData['slug'] = $this->slugService->generateUnique($user->name, UserSpeakerData::class);
                $user->speakerData()->create($speakerData);
            }

            return $user;
        });

        $user->load(['studentData', 'speakerData', 'roles']);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'data' => new AdminUserResource($user),
        ], 201);
    }

    public function update(AdminUserUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated) {
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            if (isset($validated['role'])) {
                $user->syncRoles(
                    $validated['role'] === 'user' ? [] : [$validated['role']],
                );
            }

            if (array_key_exists('student_data', $validated)) {
                if ($validated['student_data'] === null) {
                    $user->studentData()->delete();
                } else {
                    $user->studentData()->updateOrCreate(
                        ['user_id' => $user->id],
                        $validated['student_data']
                    );
                }
            }

            if (array_key_exists('speaker_data', $validated)) {
                if ($validated['speaker_data'] === null) {
                    $user->speakerData()->delete();
                } else {
                    $speakerData = $validated['speaker_data'];
                    if (empty($speakerData['slug'])) {
                        $speakerData['slug'] = $this->slugService->generateUnique($user->name, UserSpeakerData::class);
                    }
                    $user->speakerData()->updateOrCreate(
                        ['user_id' => $user->id],
                        $speakerData
                    );
                }
            }
        });

        $user->load(['studentData', 'speakerData', 'roles']);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'data' => new AdminUserResource($user),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'Usuário excluído com sucesso',
        ]);
    }

    public function restore(User $user): JsonResponse
    {
        Gate::authorize('restore', $user);

        $user->restore();
        $user->load(['studentData', 'speakerData', 'roles']);

        return response()->json([
            'message' => 'Usuário restaurado com sucesso',
            'data' => new AdminUserResource($user),
        ]);
    }
}
