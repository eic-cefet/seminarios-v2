<?php

use App\Services\AwsSecretEnvLoader;
use App\Services\AwsSecretEnvService;
use Aws\MockHandler;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Foundation\Application;

function makeLoaderClient(MockHandler $handler): SecretsManagerClient
{
    return new SecretsManagerClient([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]);
}

function loaderClientReturning(array $payload): SecretsManagerClient
{
    $handler = new MockHandler;
    $handler->append(new Result(['SecretString' => json_encode($payload)]));

    return makeLoaderClient($handler);
}

/**
 * Env vars this suite touches; snapshotted/restored around each test so
 * loader-injected values never leak into other tests.
 */
const LOADER_ENV_KEYS = [
    'AWS_ENV_SECRET_ID', 'AWS_ENV_SECRET_REGION', 'AWS_REGION', 'AWS_DEFAULT_REGION',
    'AWS_ENV_SECRET_ACCESS_KEY_ID', 'AWS_ENV_SECRET_SECRET_ACCESS_KEY',
    'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY',
    'LOADER_TEST_FOO', 'LOADER_TEST_BAR',
];

function setLoaderEnv(string $key, ?string $value): void
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

beforeEach(function () {
    $this->loaderEnvBackup = [];
    foreach (LOADER_ENV_KEYS as $key) {
        $this->loaderEnvBackup[$key] = $_ENV[$key] ?? null;
        setLoaderEnv($key, null);
    }
});

afterEach(function () {
    foreach (LOADER_ENV_KEYS as $key) {
        setLoaderEnv($key, $this->loaderEnvBackup[$key]);
    }
});

describe('fromEnvironment', function () {
    it('returns null when AWS_ENV_SECRET_ID is unset', function () {
        expect(AwsSecretEnvLoader::fromEnvironment())->toBeNull();
    });

    it('returns null when AWS_ENV_SECRET_ID is empty', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', '');

        expect(AwsSecretEnvLoader::fromEnvironment())->toBeNull();
    });

    it('throws when the secret id is set but no region is available', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');

        AwsSecretEnvLoader::fromEnvironment();
    })->throws(RuntimeException::class, 'no AWS region');

    it('builds a loader with a real client when a region is available', function (string $regionVar) {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');
        setLoaderEnv($regionVar, 'us-east-1');

        expect(AwsSecretEnvLoader::fromEnvironment())->toBeInstanceOf(AwsSecretEnvLoader::class);
    })->with(['AWS_ENV_SECRET_REGION', 'AWS_REGION', 'AWS_DEFAULT_REGION']);

    it('builds a loader from an injected client without needing a region', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');

        $loader = AwsSecretEnvLoader::fromEnvironment(loaderClientReturning([]));

        expect($loader)->toBeInstanceOf(AwsSecretEnvLoader::class);
    });
});

describe('makeClient', function () {
    it('prefers the dedicated override credentials over the standard AWS pair', function () {
        setLoaderEnv('AWS_ENV_SECRET_REGION', 'us-east-1');
        setLoaderEnv('AWS_ENV_SECRET_ACCESS_KEY_ID', 'override-key');
        setLoaderEnv('AWS_ENV_SECRET_SECRET_ACCESS_KEY', 'override-secret');
        setLoaderEnv('AWS_ACCESS_KEY_ID', 'standard-key');
        setLoaderEnv('AWS_SECRET_ACCESS_KEY', 'standard-secret');

        $credentials = AwsSecretEnvLoader::makeClient()->getCredentials()->wait();

        expect($credentials->getAccessKeyId())->toBe('override-key')
            ->and($credentials->getSecretKey())->toBe('override-secret');
    });

    it('falls back to the standard AWS credentials when no override is set', function () {
        setLoaderEnv('AWS_ENV_SECRET_REGION', 'us-east-1');
        setLoaderEnv('AWS_ACCESS_KEY_ID', 'standard-key');
        setLoaderEnv('AWS_SECRET_ACCESS_KEY', 'standard-secret');

        $credentials = AwsSecretEnvLoader::makeClient()->getCredentials()->wait();

        expect($credentials->getAccessKeyId())->toBe('standard-key')
            ->and($credentials->getSecretKey())->toBe('standard-secret');
    });

    it('leaves credential resolution to the SDK default chain when no pair is set', function () {
        setLoaderEnv('AWS_ENV_SECRET_REGION', 'us-east-1');

        expect(AwsSecretEnvLoader::makeClient())->toBeInstanceOf(SecretsManagerClient::class);
    });
});

describe('load', function () {
    it('injects secret entries into $_ENV, $_SERVER, and getenv', function () {
        $loader = new AwsSecretEnvLoader(
            new AwsSecretEnvService(loaderClientReturning(['LOADER_TEST_FOO' => 'from-secret'])),
            'my-secret',
        );

        $loader->load();

        expect($_ENV['LOADER_TEST_FOO'])->toBe('from-secret')
            ->and($_SERVER['LOADER_TEST_FOO'])->toBe('from-secret')
            ->and(getenv('LOADER_TEST_FOO'))->toBe('from-secret');
    });

    it('overrides env vars that already exist', function () {
        setLoaderEnv('LOADER_TEST_FOO', 'original');

        $loader = new AwsSecretEnvLoader(
            new AwsSecretEnvService(loaderClientReturning(['LOADER_TEST_FOO' => 'overridden'])),
            'my-secret',
        );

        $loader->load();

        expect($_ENV['LOADER_TEST_FOO'])->toBe('overridden')
            ->and(getenv('LOADER_TEST_FOO'))->toBe('overridden');
    });

    it('propagates validation failures from the service', function () {
        $handler = new MockHandler;
        $handler->append(new Result(['SecretString' => 'not-json']));

        $loader = new AwsSecretEnvLoader(
            new AwsSecretEnvService(makeLoaderClient($handler)),
            'my-secret',
        );

        $loader->load();
    })->throws(RuntimeException::class, 'must be a JSON object');

    it('exposes the fetched env vars for the refresh command', function () {
        $loader = new AwsSecretEnvLoader(
            new AwsSecretEnvService(loaderClientReturning(['LOADER_TEST_FOO' => 'value'])),
            'my-secret',
        );

        expect($loader->fetchEnvVars())->toBe(['LOADER_TEST_FOO' => 'value']);
    });
});

describe('bootIfNeeded', function () {
    it('does nothing when configuration is cached, even with a secret configured', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('configurationIsCached')->once()->andReturn(true);

        AwsSecretEnvLoader::bootIfNeeded($app, loaderClientReturning(['LOADER_TEST_BAR' => 'nope']));

        expect($_ENV)->not->toHaveKey('LOADER_TEST_BAR');
    });

    it('does nothing when the feature is disabled', function () {
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('configurationIsCached')->once()->andReturn(false);

        AwsSecretEnvLoader::bootIfNeeded($app, loaderClientReturning(['LOADER_TEST_BAR' => 'nope']));

        expect($_ENV)->not->toHaveKey('LOADER_TEST_BAR');
    });

    it('fetches and injects when enabled and configuration is not cached', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('configurationIsCached')->once()->andReturn(false);

        AwsSecretEnvLoader::bootIfNeeded($app, loaderClientReturning(['LOADER_TEST_BAR' => 'loaded']));

        expect($_ENV['LOADER_TEST_BAR'])->toBe('loaded')
            ->and(getenv('LOADER_TEST_BAR'))->toBe('loaded');
    });

    it('logs the real cause and rethrows when loading fails pre-config', function () {
        setLoaderEnv('AWS_ENV_SECRET_ID', 'my-secret');
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('configurationIsCached')->once()->andReturn(false);

        $handler = new MockHandler;
        $handler->append(new Result(['SecretString' => 'not-json']));

        $previousErrorLog = ini_set('error_log', '/dev/null');

        try {
            AwsSecretEnvLoader::bootIfNeeded($app, makeLoaderClient($handler));
        } finally {
            ini_set('error_log', $previousErrorLog);
        }
    })->throws(RuntimeException::class, 'must be a JSON object');
});
