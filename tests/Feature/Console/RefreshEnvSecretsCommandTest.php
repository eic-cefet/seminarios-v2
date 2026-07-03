<?php

use App\Console\Commands\RefreshEnvSecretsCommand;
use App\Services\AwsSecretEnvLoader;
use App\Services\AwsSecretEnvService;
use App\Services\ConfigCacheRefresher;
use Aws\MockHandler;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

function refreshLoaderWithPayload(string $secretString): AwsSecretEnvLoader
{
    $handler = new MockHandler;
    $handler->append(new Result(['SecretString' => $secretString]));

    return new AwsSecretEnvLoader(
        new AwsSecretEnvService(new SecretsManagerClient([
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => ['key' => 'test', 'secret' => 'test'],
            'handler' => $handler,
        ])),
        'my-secret',
    );
}

it('no-ops when the feature is disabled', function () {
    config(['env-secrets.secret_id' => null]);
    $this->mock(ConfigCacheRefresher::class)->shouldNotReceive('refresh');

    $this->artisan('env-secrets:refresh')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();
});

it('re-caches config and stores the hash when the secret changed', function () {
    config(['env-secrets.secret_id' => 'my-secret']);
    $this->app->instance(AwsSecretEnvLoader::class, refreshLoaderWithPayload(json_encode(['FOO' => 'bar'])));
    $this->mock(ConfigCacheRefresher::class)->shouldReceive('refresh')->once();

    $this->artisan('env-secrets:refresh')
        ->expectsOutputToContain('re-cached')
        ->assertSuccessful();

    expect(Cache::get(RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY))
        ->toBe(hash('sha256', json_encode(['FOO' => 'bar'])));
});

it('does nothing when the secret is unchanged', function () {
    config(['env-secrets.secret_id' => 'my-secret']);
    Cache::forever(RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY, hash('sha256', json_encode(['FOO' => 'bar'])));
    $this->app->instance(AwsSecretEnvLoader::class, refreshLoaderWithPayload(json_encode(['FOO' => 'bar'])));
    $this->mock(ConfigCacheRefresher::class)->shouldNotReceive('refresh');

    $this->artisan('env-secrets:refresh')
        ->expectsOutputToContain('unchanged')
        ->assertSuccessful();
});

it('keeps the current cache and stored hash when the fetch fails', function () {
    config(['env-secrets.secret_id' => 'my-secret']);
    Cache::forever(RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY, 'previous-hash');
    $this->app->instance(AwsSecretEnvLoader::class, refreshLoaderWithPayload('not-json'));
    $this->mock(ConfigCacheRefresher::class)->shouldNotReceive('refresh');

    $this->artisan('env-secrets:refresh')->assertFailed();

    expect(Cache::get(RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY))->toBe('previous-hash');
});

it('fails visibly when enabled by config but no region is available anywhere', function () {
    config(['env-secrets.secret_id' => 'my-secret', 'env-secrets.region' => null]);
    $this->mock(ConfigCacheRefresher::class)->shouldNotReceive('refresh');

    $regionKeys = ['AWS_ENV_SECRET_REGION', 'AWS_REGION', 'AWS_DEFAULT_REGION'];
    $backup = [];
    foreach ($regionKeys as $key) {
        $backup[$key] = $_ENV[$key] ?? null;
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    try {
        $this->artisan('env-secrets:refresh')->assertFailed();
    } finally {
        foreach ($regionKeys as $key) {
            if ($backup[$key] !== null) {
                $_ENV[$key] = $_SERVER[$key] = $backup[$key];
                putenv("{$key}={$backup[$key]}");
            }
        }
    }
});

it('resolves the loader from the container when the baked config is set', function () {
    config(['env-secrets.secret_id' => 'my-secret', 'env-secrets.region' => 'us-east-1']);

    expect(app(AwsSecretEnvLoader::class))->toBeInstanceOf(AwsSecretEnvLoader::class);
});

it('throws from the container binding when the baked secret id is empty', function () {
    config(['env-secrets.secret_id' => null]);

    expect(fn () => app(AwsSecretEnvLoader::class))
        ->toThrow(RuntimeException::class, 'not configured');
});

it('is scheduled every five minutes', function () {
    $events = collect(app(Schedule::class)->events());

    $event = $events->first(fn ($event) => str_contains((string) $event->command, 'env-secrets:refresh'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *');
});
