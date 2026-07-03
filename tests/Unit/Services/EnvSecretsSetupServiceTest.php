<?php

use App\Console\Commands\RefreshEnvSecretsCommand;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Services\ConfigCacheRefresher;
use App\Services\EnvFileWriter;
use App\Services\EnvSecretsSetupService;
use Aws\MockHandler;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function setupClientReturning(string $secretString): SecretsManagerClient
{
    $handler = new MockHandler;
    $handler->append(new Result(['SecretString' => $secretString]));

    return new SecretsManagerClient([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]);
}

function setupEnv(string $key, ?string $value): void
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

const SETUP_ENV_KEYS = [
    'AWS_ENV_SECRET_REGION', 'AWS_REGION', 'AWS_DEFAULT_REGION',
    'AWS_ENV_SECRET_ACCESS_KEY_ID', 'AWS_ENV_SECRET_SECRET_ACCESS_KEY',
    'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY',
];

beforeEach(function () {
    $this->setupEnvBackup = [];
    foreach (SETUP_ENV_KEYS as $key) {
        $this->setupEnvBackup[$key] = $_ENV[$key] ?? null;
        setupEnv($key, null);
    }

    config([
        'env-secrets.region' => null,
        'env-secrets.access_key_id' => null,
        'env-secrets.secret_access_key' => null,
    ]);
});

afterEach(function () {
    foreach (SETUP_ENV_KEYS as $key) {
        setupEnv($key, $this->setupEnvBackup[$key]);
    }
});

function makeSetupService(?EnvFileWriter $writer = null, ?ConfigCacheRefresher $refresher = null): EnvSecretsSetupService
{
    return new EnvSecretsSetupService(
        $writer ?? Mockery::mock(EnvFileWriter::class)->shouldIgnoreMissing(),
        $refresher ?? Mockery::mock(ConfigCacheRefresher::class)->shouldIgnoreMissing(),
    );
}

describe('validate', function () {
    it('fetches and returns the env map with an injected client', function () {
        $service = makeSetupService();

        $result = $service->validate(
            ['secret_id' => 'my-secret'],
            setupClientReturning(json_encode(['FOO' => 'bar'])),
        );

        expect($result)->toBe(['FOO' => 'bar']);
    });

    it('propagates payload validation failures', function () {
        makeSetupService()->validate(
            ['secret_id' => 'my-secret'],
            setupClientReturning('not-json'),
        );
    })->throws(RuntimeException::class, 'must be a JSON object');

    it('throws when no region is available anywhere', function () {
        makeSetupService()->validate(['secret_id' => 'my-secret']);
    })->throws(RuntimeException::class, 'region');
});

describe('makeClient', function () {
    it('prefers the submitted region and credentials', function () {
        setupEnv('AWS_ENV_SECRET_REGION', 'sa-east-1');
        setupEnv('AWS_ENV_SECRET_ACCESS_KEY_ID', 'env-key');
        setupEnv('AWS_ENV_SECRET_SECRET_ACCESS_KEY', 'env-secret');

        $client = makeSetupService()->makeClient([
            'secret_id' => 'my-secret',
            'region' => 'us-east-1',
            'access_key_id' => 'input-key',
            'secret_access_key' => 'input-secret',
        ]);

        $credentials = $client->getCredentials()->wait();

        expect($client->getRegion())->toBe('us-east-1')
            ->and($credentials->getAccessKeyId())->toBe('input-key')
            ->and($credentials->getSecretKey())->toBe('input-secret');
    });

    it('falls back to the baked config region and credentials when input and env are empty', function () {
        config([
            'env-secrets.region' => 'ca-central-1',
            'env-secrets.access_key_id' => 'baked-key',
            'env-secrets.secret_access_key' => 'baked-secret',
        ]);

        $client = makeSetupService()->makeClient(['secret_id' => 'my-secret']);

        $credentials = $client->getCredentials()->wait();

        expect($client->getRegion())->toBe('ca-central-1')
            ->and($credentials->getAccessKeyId())->toBe('baked-key')
            ->and($credentials->getSecretKey())->toBe('baked-secret');
    });

    it('falls back to the dedicated env credentials and region', function () {
        setupEnv('AWS_ENV_SECRET_REGION', 'sa-east-1');
        setupEnv('AWS_ENV_SECRET_ACCESS_KEY_ID', 'env-key');
        setupEnv('AWS_ENV_SECRET_SECRET_ACCESS_KEY', 'env-secret');

        $client = makeSetupService()->makeClient(['secret_id' => 'my-secret']);

        expect($client->getRegion())->toBe('sa-east-1')
            ->and($client->getCredentials()->wait()->getAccessKeyId())->toBe('env-key');
    });

    it('falls back to the standard AWS pair', function () {
        setupEnv('AWS_REGION', 'us-west-2');
        setupEnv('AWS_ACCESS_KEY_ID', 'standard-key');
        setupEnv('AWS_SECRET_ACCESS_KEY', 'standard-secret');

        $client = makeSetupService()->makeClient(['secret_id' => 'my-secret']);

        expect($client->getRegion())->toBe('us-west-2')
            ->and($client->getCredentials()->wait()->getAccessKeyId())->toBe('standard-key');
    });

    it('leaves credentials to the SDK default chain when no pair exists', function () {
        setupEnv('AWS_DEFAULT_REGION', 'eu-west-1');

        expect(makeSetupService()->makeClient(['secret_id' => 'my-secret']))
            ->toBeInstanceOf(SecretsManagerClient::class);
    });
});

describe('apply', function () {
    it('writes the env keys, refreshes, stores the hash, audits, and returns sorted key names', function () {
        $writer = Mockery::mock(EnvFileWriter::class);
        $writer->shouldReceive('setValues')->once()->with([
            'AWS_ENV_SECRET_ID' => 'my-secret',
            'AWS_ENV_SECRET_REGION' => 'us-east-1',
            'AWS_ENV_SECRET_ACCESS_KEY_ID' => null,
            'AWS_ENV_SECRET_SECRET_ACCESS_KEY' => null,
        ]);
        $refresher = Mockery::mock(ConfigCacheRefresher::class);
        $refresher->shouldReceive('refresh')->once();

        $fetched = ['ZED' => '1', 'ALPHA' => '2'];
        $keys = makeSetupService($writer, $refresher)->apply(
            ['secret_id' => 'my-secret', 'region' => 'us-east-1', 'access_key_id' => '', 'secret_access_key' => ''],
            $fetched,
        );

        expect($keys)->toBe(['ALPHA', 'ZED'])
            ->and(Cache::get(RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY))
            ->toBe(hash('sha256', json_encode($fetched, JSON_THROW_ON_ERROR)));

        $log = AuditLog::query()->where('event_name', AuditEvent::EnvSecretsUpdated->value)->first();
        expect($log)->not->toBeNull()
            ->and($log->event_data['secret_id'])->toBe('my-secret')
            ->and($log->event_data['secret_keys'])->toBe(['ALPHA', 'ZED'])
            ->and($log->event_data['access_key_id_set'])->toBeFalse()
            ->and(json_encode($log->event_data))->not->toContain('input-secret');
    });

    it('passes submitted credentials through and marks them set in the audit row', function () {
        $writer = Mockery::mock(EnvFileWriter::class);
        $writer->shouldReceive('setValues')->once()->with([
            'AWS_ENV_SECRET_ID' => 'my-secret',
            'AWS_ENV_SECRET_REGION' => null,
            'AWS_ENV_SECRET_ACCESS_KEY_ID' => 'the-key',
            'AWS_ENV_SECRET_SECRET_ACCESS_KEY' => 'the-secret',
        ]);
        $refresher = Mockery::mock(ConfigCacheRefresher::class);
        $refresher->shouldReceive('refresh')->once();

        makeSetupService($writer, $refresher)->apply(
            ['secret_id' => 'my-secret', 'access_key_id' => 'the-key', 'secret_access_key' => 'the-secret'],
            ['FOO' => 'bar'],
        );

        $log = AuditLog::query()->where('event_name', AuditEvent::EnvSecretsUpdated->value)->first();
        expect($log->event_data['access_key_id_set'])->toBeTrue()
            ->and(json_encode($log->event_data))->not->toContain('the-secret');
    });
});
