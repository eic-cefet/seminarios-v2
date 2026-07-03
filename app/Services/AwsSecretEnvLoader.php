<?php

namespace App\Services;

use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;
use RuntimeException;
use Throwable;

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

        try {
            static::fromEnvironment($client)?->load();
        } catch (Throwable $e) {
            /*
             * This runs before the config binding exists, so the framework's
             * exception reporter fatals with a misleading "Target class
             * [config] does not exist" — surface the real cause first.
             */
            error_log("env-secrets: failed to load AWS secret env overrides: {$e->getMessage()}");

            throw $e;
        }
    }

    public static function fromEnvironment(?SecretsManagerClient $client = null): ?self
    {
        $secretId = Env::get('AWS_ENV_SECRET_ID') ?: null;

        if ($secretId === null) {
            return null;
        }

        return new self(new AwsSecretEnvService($client ?? self::makeClient()), $secretId);
    }

    /**
     * Build from the baked config('env-secrets') array — the only reliable
     * source on cached-config deployments, where .env is never loaded.
     *
     * @param  array{secret_id?: ?string, region?: ?string, access_key_id?: ?string, secret_access_key?: ?string}  $settings
     */
    public static function fromConfig(array $settings, ?SecretsManagerClient $client = null): ?self
    {
        $secretId = ($settings['secret_id'] ?? '') ?: null;

        if ($secretId === null) {
            return null;
        }

        return new self(new AwsSecretEnvService($client ?? self::makeClient($settings)), $secretId);
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

    /**
     * Region and credentials resolve settings-first (baked config values on
     * cached deployments), then the dedicated AWS_ENV_SECRET_ACCESS_KEY_ID /
     * AWS_ENV_SECRET_SECRET_ACCESS_KEY pair (so the secret fetch can use
     * different credentials than S3/MinIO), then the standard
     * AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY pair, then the SDK default
     * provider chain (IAM role, etc.).
     *
     * @param  array{region?: ?string, access_key_id?: ?string, secret_access_key?: ?string}  $settings
     */
    public static function makeClient(array $settings = []): SecretsManagerClient
    {
        $region = ($settings['region'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_REGION')
            ?: Env::get('AWS_REGION')
            ?: Env::get('AWS_DEFAULT_REGION')
            ?: null;

        if ($region === null) {
            throw new RuntimeException('AWS_ENV_SECRET_ID is set but no AWS region is available (set AWS_ENV_SECRET_REGION, AWS_REGION, or AWS_DEFAULT_REGION).');
        }

        $config = [
            'version' => 'latest',
            'region' => $region,
            'http' => ['connect_timeout' => 5, 'timeout' => 15],
        ];

        $key = ($settings['access_key_id'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_ACCESS_KEY_ID')
            ?: Env::get('AWS_ACCESS_KEY_ID')
            ?: null;
        $secret = ($settings['secret_access_key'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_SECRET_ACCESS_KEY')
            ?: Env::get('AWS_SECRET_ACCESS_KEY')
            ?: null;

        if ($key !== null && $secret !== null) {
            $config['credentials'] = ['key' => $key, 'secret' => $secret];
        }

        return new SecretsManagerClient($config);
    }
}
