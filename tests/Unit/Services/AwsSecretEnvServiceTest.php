<?php

use App\Services\AwsSecretEnvService;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;

function makeSecretsClient(MockHandler $handler): SecretsManagerClient
{
    return new SecretsManagerClient([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => $handler,
    ]);
}

function makeService(MockHandler $handler): AwsSecretEnvService
{
    return new AwsSecretEnvService(makeSecretsClient($handler));
}

function mockSecretString(string $payload): MockHandler
{
    $handler = new MockHandler;
    $handler->append(new Result(['SecretString' => $payload]));

    return $handler;
}

describe('fetchEnvVars', function () {
    it('returns the key-value map from a JSON object secret', function () {
        $service = makeService(mockSecretString(json_encode([
            'APP_KEY' => 'base64:abc123',
            'DB_PASSWORD' => 's3cret',
        ])));

        expect($service->fetchEnvVars('my-secret'))->toBe([
            'APP_KEY' => 'base64:abc123',
            'DB_PASSWORD' => 's3cret',
        ]);
    });

    it('casts integer, float, and boolean values to strings', function () {
        $service = makeService(mockSecretString(json_encode([
            'MAIL_PORT' => 2525,
            'RATE' => 1.5,
            'FEATURE_ON' => true,
            'FEATURE_OFF' => false,
        ])));

        expect($service->fetchEnvVars('my-secret'))->toBe([
            'MAIL_PORT' => '2525',
            'RATE' => '1.5',
            'FEATURE_ON' => 'true',
            'FEATURE_OFF' => 'false',
        ]);
    });

    it('returns an empty array for an empty JSON object', function () {
        $service = makeService(mockSecretString('{}'));

        expect($service->fetchEnvVars('my-secret'))->toBe([]);
    });

    it('rejects a secret without a SecretString (binary secret)', function () {
        $handler = new MockHandler;
        $handler->append(new Result(['SecretBinary' => 'AAAA']));

        makeService($handler)->fetchEnvVars('my-secret');
    })->throws(RuntimeException::class, 'must contain a string payload');

    it('rejects a payload that is not valid JSON', function () {
        makeService(mockSecretString('not-json'))->fetchEnvVars('my-secret');
    })->throws(RuntimeException::class, 'must be a JSON object');

    it('rejects a JSON payload that is not an object', function () {
        makeService(mockSecretString('["a", "b"]'))->fetchEnvVars('my-secret');
    })->throws(RuntimeException::class, 'must be a JSON object');

    it('rejects invalid environment variable names', function (string $key) {
        makeService(mockSecretString(json_encode([$key => 'value'])))
            ->fetchEnvVars('my-secret');
    })->with([
        'leading digit' => '1BAD',
        'hyphen' => 'BAD-NAME',
        'space' => 'BAD NAME',
        'empty' => '',
    ])->throws(RuntimeException::class, 'Invalid environment variable name');

    it('rejects non-scalar values', function (string $payload) {
        makeService(mockSecretString($payload))->fetchEnvVars('my-secret');
    })->with([
        'null value' => '{"KEY": null}',
        'array value' => '{"KEY": [1, 2]}',
        'object value' => '{"KEY": {"nested": true}}',
    ])->throws(RuntimeException::class, 'must be a string, number, or boolean');

    it('propagates AWS API errors', function () {
        $handler = new MockHandler;
        $handler->append(function ($command) {
            return new AwsException('Access denied', $command);
        });

        makeService($handler)->fetchEnvVars('my-secret');
    })->throws(AwsException::class);
});

describe('toShellExports', function () {
    it('renders one export line per variable with single-quoted values', function () {
        $service = makeService(new MockHandler);

        $output = $service->toShellExports([
            'APP_KEY' => 'base64:abc123',
            'MAIL_PORT' => '2525',
        ]);

        expect($output)->toBe("export APP_KEY='base64:abc123'\nexport MAIL_PORT='2525'\n");
    });

    it('escapes single quotes inside values', function () {
        $service = makeService(new MockHandler);

        expect($service->toShellExports(['GREETING' => "it's fine"]))
            ->toBe("export GREETING='it'\\''s fine'\n");
    });

    it('returns an empty string for an empty map', function () {
        $service = makeService(new MockHandler);

        expect($service->toShellExports([]))->toBe('');
    });
});
