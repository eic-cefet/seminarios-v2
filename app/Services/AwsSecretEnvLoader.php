<?php

namespace App\Services;

use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;
use RuntimeException;

/**
 * Boot-time environment overrides from AWS Secrets Manager.
 *
 * Registered in bootstrap/app.php to run before the LoadConfiguration
 * bootstrapper, so every runtime (Apache request, Octane worker, queue:work,
 * schedule:run, any artisan command) sees the overridden values when config
 * files call env(). When configuration is cached the fetch is skipped
 * entirely: `php artisan config:cache` performs a fresh boot that runs this
 * loader first, baking the overrides into the cache, so cached deployments
 * make zero AWS calls at runtime.
 */
class AwsSecretEnvLoader
{
    public function __construct(private AwsSecretEnvService $service, private string $secretId) {}

    public static function bootIfNeeded(Application $app, ?SecretsManagerClient $client = null): void
    {
        if ($app->configurationIsCached()) {
            return;
        }

        static::fromEnvironment($client)?->load();
    }

    public static function fromEnvironment(?SecretsManagerClient $client = null): ?self
    {
        $secretId = Env::get('AWS_ENV_SECRET_ID') ?: null;

        if ($secretId === null) {
            return null;
        }

        return new self(new AwsSecretEnvService($client ?? self::makeClient()), $secretId);
    }

    public function load(): void
    {
        foreach ($this->fetchEnvVars() as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * @return array<string, string>
     */
    public function fetchEnvVars(): array
    {
        return $this->service->fetchEnvVars($this->secretId);
    }

    private static function makeClient(): SecretsManagerClient
    {
        $region = Env::get('AWS_ENV_SECRET_REGION')
            ?: Env::get('AWS_REGION')
            ?: Env::get('AWS_DEFAULT_REGION')
            ?: null;

        if ($region === null) {
            throw new RuntimeException('AWS_ENV_SECRET_ID is set but no AWS region is available (set AWS_ENV_SECRET_REGION, AWS_REGION, or AWS_DEFAULT_REGION).');
        }

        return new SecretsManagerClient([
            'version' => 'latest',
            'region' => $region,
            'http' => ['connect_timeout' => 5, 'timeout' => 15],
        ]);
    }
}
