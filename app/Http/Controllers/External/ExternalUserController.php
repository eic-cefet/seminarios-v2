<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalUserStoreRequest;
use App\Http\Requests\External\ExternalUserUpdateRequest;
use App\Http\Resources\External\ExternalUserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExternalUserController extends Controller
{
    use EscapesLikeWildcards;

    #[QueryParameter('search', description: 'Search by name or email', type: 'string', example: 'joao')]
    #[QueryParameter('email', description: 'Filter by exact email address', type: 'string', example: 'joao@cefet-rj.br')]
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with('speakerData');

        if ($search = $request->string('search')->trim()->toString()) {
            $escaped = $this->escapeLike($search);
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
        Gate::authorize('view', $user);

        $user->load('speakerData');

        return new ExternalUserResource($user);
    }

    public function store(ExternalUserStoreRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $user->load('speakerData');

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new ExternalUserResource($user),
        ], 201);
    }

    public function update(ExternalUserUpdateRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());
        $user->load('speakerData');

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new ExternalUserResource($user),
        ]);
    }
}
