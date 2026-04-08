<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Http\Resources\External\ExternalUserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExternalUserController extends Controller
{
    #[QueryParameter('search', description: 'Search by name or email', type: 'string', example: 'joao')]
    #[QueryParameter('email', description: 'Filter by exact email address', type: 'string', example: 'joao@cefet-rj.br')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with('speakerData');

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = addcslashes($search, '%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        if ($email = $request->string('email')->trim()->toString()) {
            $query->where('email', $email);
        }

        $users = $query->orderBy('name')->paginate(15);

        return ExternalUserResource::collection($users);
    }

    public function show(User $user): ExternalUserResource
    {
        $user->load('speakerData');

        return new ExternalUserResource($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
        ]);

        $user = User::create($validated);
        $user->load('speakerData');

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new ExternalUserResource($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'username' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:users,username,'.$user->id],
        ]);

        $user->update($validated);
        $user->load('speakerData');

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new ExternalUserResource($user),
        ]);
    }
}
