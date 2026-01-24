<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Models\UserSpeakerData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['studentData', 'speakerData', 'roles'])
            ->orderByDesc('created_at');

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        // Search by name, email, or username
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->string('role')->trim()->toString()) {
            $query->role($role);
        }

        return AdminUserResource::collection($query->paginate(15));
    }

    public function show(User $user): AdminUserResource
    {
        Gate::authorize('view', $user);

        $user->load(['studentData', 'speakerData', 'roles']);

        return new AdminUserResource($user);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'teacher'])],
            'student_data' => ['nullable', 'array'],
            'student_data.course_name' => ['nullable', 'string', 'max:255'],
            'student_data.course_situation' => ['nullable', 'string', Rule::in(['studying', 'graduated'])],
            'student_data.course_role' => ['nullable', 'string'],
            'speaker_data' => ['nullable', 'array'],
            'speaker_data.institution' => ['nullable', 'string', 'max:255'],
            'speaker_data.description' => ['nullable', 'string'],
        ]);

        // Generate random 24-char password if not provided
        $password = $validated['password'] ?? Str::random(24);

        $user = DB::transaction(function () use ($validated, $password) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);

            if (isset($validated['role'])) {
                $user->assignRole($validated['role']);
            }

            if (! empty($validated['student_data'])) {
                $user->studentData()->create($validated['student_data']);
            }

            if (! empty($validated['speaker_data'])) {
                $speakerData = $validated['speaker_data'];
                $speakerData['slug'] = $this->generateSpeakerSlug($user->name);
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

    public function update(Request $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in(['admin', 'teacher', 'user'])],
            'student_data' => ['nullable', 'array'],
            'student_data.course_name' => ['nullable', 'string', 'max:255'],
            'student_data.course_situation' => ['nullable', 'string', Rule::in(['studying', 'graduated'])],
            'student_data.course_role' => ['nullable', 'string'],
            'speaker_data' => ['nullable', 'array'],
            'speaker_data.slug' => ['nullable', 'string', 'max:255'],
            'speaker_data.institution' => ['nullable', 'string', 'max:255'],
            'speaker_data.description' => ['nullable', 'string'],
        ]);

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
                $user->syncRoles([$validated['role']]);
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
                        $speakerData['slug'] = $this->generateSpeakerSlug($user->name);
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

        if ($request->user()->id === $user->id) {
            throw ApiException::cannotDeleteSelf();
        }

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

    private function generateSpeakerSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (UserSpeakerData::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
