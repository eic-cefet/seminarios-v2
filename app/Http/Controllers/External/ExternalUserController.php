<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Concerns\EscapesLikeWildcards;
use App\Http\Controllers\Controller;
use App\Http\Requests\External\ExternalUserStoreRequest;
use App\Http\Requests\External\ExternalUserUpdateRequest;
use App\Http\Resources\External\ExternalResourceCollection;
use App\Http\Resources\External\ExternalUserResource;
use App\Models\User;
use App\Support\External\IncludesParser;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExternalUserController extends Controller
{
    use EscapesLikeWildcards;

    /**
     * Whitelist mapping client include keys to eager-load paths.
     *
     * @var array<string, string>
     */
    private const INCLUDE_MAP = [
        'speaker_data' => 'speakerData',
    ];

    #[QueryParameter('search', description: 'Search by name or email', type: 'string', example: 'joao')]
    #[QueryParameter('email', description: 'Filter by exact email address', type: 'string', example: 'joao@cefet-rj.br')]
    #[QueryParameter('include', description: 'Comma-separated relations to eager-load. Allowed: speaker_data.', type: 'string', example: 'speaker_data')]
    public function index(Request $request): ExternalResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        $includes = $this->resolveIncludes($request);
        if ($includes !== []) {
            $query->with($includes);
        }

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

        $lastModified = collect($users->items())->max('updated_at') ?? now();
        $request->attributes->set('external_last_modified', $lastModified);

        return new ExternalResourceCollection($users, ExternalUserResource::class);
    }

    #[QueryParameter('include', description: 'Comma-separated relations to eager-load. Allowed: speaker_data.', type: 'string', example: 'speaker_data')]
    public function show(Request $request, User $user): ExternalUserResource
    {
        Gate::authorize('view', $user);

        $includes = $this->resolveIncludes($request);
        if ($includes !== []) {
            $user->load($includes);
        }

        $request->attributes->set('external_last_modified', $user->updated_at);

        return new ExternalUserResource($user);
    }

    #[BodyParameter('name', description: 'Full name', type: 'string', example: 'João Silva')]
    #[BodyParameter('email', description: 'Email address (must be unique across all users)', type: 'string', example: 'joao@cefet-rj.br')]
    public function store(ExternalUserStoreRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new ExternalUserResource($user),
        ], 201);
    }

    #[BodyParameter('name', description: 'Full name', type: 'string', example: 'João Silva')]
    #[BodyParameter('email', description: 'Email address (must be unique across all users, excluding the current record)', type: 'string', example: 'joao@cefet-rj.br')]
    public function update(ExternalUserUpdateRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new ExternalUserResource($user),
        ]);
    }

    /**
     * Resolve the `include` query parameter into eager-load paths.
     *
     * @return list<string>
     */
    private function resolveIncludes(Request $request): array
    {
        try {
            return IncludesParser::resolve($request->query('include'), self::INCLUDE_MAP);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['include' => $e->getMessage()]);
        }
    }
}
