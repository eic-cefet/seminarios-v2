<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEnvSecretsRequest;
use App\Services\EnvSecretsSetupService;
use App\Services\FeatureFlags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use RuntimeException;

class AdminEnvSecretsController extends Controller
{
    public function __construct(private readonly EnvSecretsSetupService $setupService) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $liveSecretId = Env::get('AWS_ENV_SECRET_ID') ?: null;

        return response()->json(['data' => [
            'secret_id' => $liveSecretId,
            'region' => Env::get('AWS_ENV_SECRET_REGION') ?: null,
            'access_key_id_set' => (bool) (Env::get('AWS_ENV_SECRET_ACCESS_KEY_ID') ?: null),
            'secret_access_key_set' => (bool) (Env::get('AWS_ENV_SECRET_SECRET_ACCESS_KEY') ?: null),
            'applied' => $liveSecretId !== null && $liveSecretId === (config('env-secrets.secret_id') ?: null),
        ]]);
    }

    public function update(UpdateEnvSecretsRequest $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $input = $request->validated();

        try {
            $fetchedEnvVars = $this->setupService->validate($input);
        } catch (RuntimeException $e) {
            throw ApiException::validation(['secret_id' => [$e->getMessage()]]);
        }

        $keys = $this->setupService->apply($input, $fetchedEnvVars);

        return response()->json(['data' => [
            'applied' => true,
            'keys' => $keys,
            'count' => count($keys),
        ]]);
    }

    private function authorizeAccess(Request $request): void
    {
        if (FeatureFlags::disabled('env_secrets_setup')) {
            throw ApiException::notFound();
        }

        if (! $request->user()?->hasRole(Role::Admin)) {
            throw ApiException::forbidden();
        }
    }
}
