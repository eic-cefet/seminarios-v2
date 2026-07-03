<?php

use App\Services\EnvSecretsSetupService;

function envSecretsSetEnv(string $key, ?string $value): void
{
    if ($value === null) {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    } else {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

const ENV_SECRETS_TEST_KEYS = [
    'AWS_ENV_SECRET_ID', 'AWS_ENV_SECRET_REGION',
    'AWS_ENV_SECRET_ACCESS_KEY_ID', 'AWS_ENV_SECRET_SECRET_ACCESS_KEY',
];

beforeEach(function () {
    config(['features.env_secrets_setup.enabled' => true]);
    $this->envSecretsBackup = [];
    foreach (ENV_SECRETS_TEST_KEYS as $key) {
        $this->envSecretsBackup[$key] = $_ENV[$key] ?? null;
    }
});

afterEach(function () {
    foreach (ENV_SECRETS_TEST_KEYS as $key) {
        envSecretsSetEnv($key, $this->envSecretsBackup[$key]);
    }
});

describe('GET /admin/system/env-secrets', function () {
    it('returns 404 when the feature flag is disabled', function () {
        config(['features.env_secrets_setup.enabled' => false]);
        actingAsAdmin();

        $this->getJson('/api/admin/system/env-secrets')->assertNotFound();
    });

    it('returns 403 for teacher users', function () {
        actingAsTeacher();

        $this->getJson('/api/admin/system/env-secrets')->assertForbidden();
    });

    it('requires authentication', function () {
        $this->getJson('/api/admin/system/env-secrets')->assertUnauthorized();
    });

    it('returns the current live state with masked credentials', function () {
        actingAsAdmin();
        envSecretsSetEnv('AWS_ENV_SECRET_ID', 'live-secret');
        envSecretsSetEnv('AWS_ENV_SECRET_REGION', 'us-east-1');
        envSecretsSetEnv('AWS_ENV_SECRET_ACCESS_KEY_ID', 'a-key');
        envSecretsSetEnv('AWS_ENV_SECRET_SECRET_ACCESS_KEY', null);
        config(['env-secrets.secret_id' => 'live-secret']);

        $this->getJson('/api/admin/system/env-secrets')
            ->assertSuccessful()
            ->assertExactJson(['data' => [
                'secret_id' => 'live-secret',
                'region' => 'us-east-1',
                'access_key_id_set' => true,
                'secret_access_key_set' => false,
                'applied' => true,
            ]]);
    });

    it('reports applied=false when the baked config lags the live env', function () {
        actingAsAdmin();
        envSecretsSetEnv('AWS_ENV_SECRET_ID', 'new-secret');
        config(['env-secrets.secret_id' => 'old-secret']);

        $this->getJson('/api/admin/system/env-secrets')
            ->assertSuccessful()
            ->assertJsonPath('data.applied', false);
    });
});

describe('PUT /admin/system/env-secrets', function () {
    it('returns 404 when the feature flag is disabled', function () {
        config(['features.env_secrets_setup.enabled' => false]);
        actingAsAdmin();

        $this->putJson('/api/admin/system/env-secrets', ['secret_id' => 'x'])->assertNotFound();
    });

    it('returns 403 for teacher users', function () {
        actingAsTeacher();

        $this->putJson('/api/admin/system/env-secrets', ['secret_id' => 'x'])->assertForbidden();
    });

    it('returns 404 for invalid payloads when the feature flag is disabled', function () {
        config(['features.env_secrets_setup.enabled' => false]);
        actingAsAdmin();

        $this->putJson('/api/admin/system/env-secrets', [])->assertNotFound();
    });

    it('returns 403 for teachers even with an invalid payload', function () {
        actingAsTeacher();

        $this->putJson('/api/admin/system/env-secrets', [])->assertForbidden();
    });

    it('rejects invalid payloads', function (array $payload, string $errorField) {
        actingAsAdmin();

        $this->putJson('/api/admin/system/env-secrets', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errorField);
    })->with([
        'missing secret id' => [[], 'secret_id'],
        'secret id with quote' => [['secret_id' => "bad'id"], 'secret_id'],
        'secret id with space' => [['secret_id' => 'bad id'], 'secret_id'],
        'uppercase region' => [['secret_id' => 'ok', 'region' => 'US-EAST-1'], 'region'],
        'access key without secret' => [['secret_id' => 'ok', 'access_key_id' => 'AKIA123'], 'secret_access_key'],
        'secret without access key' => [['secret_id' => 'ok', 'secret_access_key' => 'shhh'], 'access_key_id'],
    ]);

    it('returns 422 and applies nothing when the AWS validation fetch fails', function () {
        actingAsAdmin();
        $service = $this->mock(EnvSecretsSetupService::class);
        $service->shouldReceive('validate')->once()
            ->andThrow(new RuntimeException("Secret 'my-secret' must be a JSON object mapping env names to values."));
        $service->shouldNotReceive('apply');

        $this->putJson('/api/admin/system/env-secrets', ['secret_id' => 'my-secret'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('secret_id');
    });

    it('validates then applies and returns the secret key names', function () {
        actingAsAdmin();
        $payload = ['secret_id' => 'my-secret', 'region' => 'us-east-1'];
        $fetched = ['DB_PASSWORD' => 'x', 'APP_KEY' => 'y'];

        $service = $this->mock(EnvSecretsSetupService::class);
        $service->shouldReceive('validate')->once()
            ->withArgs(fn (array $input) => $input['secret_id'] === 'my-secret' && $input['region'] === 'us-east-1')
            ->andReturn($fetched);
        $service->shouldReceive('apply')->once()
            ->withArgs(fn (array $input, array $vars) => $vars === $fetched)
            ->andReturn(['APP_KEY', 'DB_PASSWORD']);

        $this->putJson('/api/admin/system/env-secrets', $payload)
            ->assertSuccessful()
            ->assertExactJson(['data' => [
                'applied' => true,
                'keys' => ['APP_KEY', 'DB_PASSWORD'],
                'count' => 2,
            ]]);
    });
});
