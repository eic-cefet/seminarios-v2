<?php

namespace App\Services;

use App\Console\Commands\RefreshEnvSecretsCommand;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Applies AWS env-secret settings submitted through the hidden admin screen.
 * validate() must succeed (a real fetch of the secret) before apply() writes
 * anything: config:cache clears the old cache before rebuilding, so applying
 * unvalidated values could take the whole site down at the next boot.
 */
class EnvSecretsSetupService
{
    public function __construct(
        private EnvFileWriter $envFileWriter,
        private ConfigCacheRefresher $refresher,
    ) {}

    /**
     * @param  array{secret_id: string, region?: ?string, access_key_id?: ?string, secret_access_key?: ?string}  $input
     * @return array<string, string>
     */
    public function validate(array $input, ?SecretsManagerClient $client = null): array
    {
        $service = new AwsSecretEnvService($client ?? $this->makeClient($input));

        return $service->fetchEnvVars($input['secret_id']);
    }

    /**
     * @param  array{secret_id: string, region?: ?string, access_key_id?: ?string, secret_access_key?: ?string}  $input
     * @param  array<string, string>  $fetchedEnvVars
     * @return list<string>
     */
    public function apply(array $input, array $fetchedEnvVars): array
    {
        $region = ($input['region'] ?? '') !== '' ? $input['region'] : null;
        $accessKeyId = ($input['access_key_id'] ?? '') !== '' ? $input['access_key_id'] : null;
        $secretAccessKey = ($input['secret_access_key'] ?? '') !== '' ? $input['secret_access_key'] : null;

        $this->envFileWriter->setValues([
            'AWS_ENV_SECRET_ID' => $input['secret_id'],
            'AWS_ENV_SECRET_REGION' => $region,
            'AWS_ENV_SECRET_ACCESS_KEY_ID' => $accessKeyId,
            'AWS_ENV_SECRET_SECRET_ACCESS_KEY' => $secretAccessKey,
        ]);

        $this->refresher->refresh();

        Cache::forever(
            RefreshEnvSecretsCommand::LAST_HASH_CACHE_KEY,
            hash('sha256', json_encode($fetchedEnvVars, JSON_THROW_ON_ERROR)),
        );

        $keys = array_keys($fetchedEnvVars);
        sort($keys);

        AuditLog::record(AuditEvent::EnvSecretsUpdated, eventData: [
            'secret_id' => $input['secret_id'],
            'region' => $region,
            'access_key_id_set' => $accessKeyId !== null,
            'secret_access_key_set' => $secretAccessKey !== null,
            'secret_keys' => $keys,
        ]);

        return $keys;
    }

    /**
     * Region and credentials resolve input-first, then the same env fallback
     * chains AwsSecretEnvLoader uses, then the SDK default provider chain.
     *
     * @param  array{secret_id: string, region?: ?string, access_key_id?: ?string, secret_access_key?: ?string}  $input
     */
    public function makeClient(array $input): SecretsManagerClient
    {
        $region = ($input['region'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_REGION')
            ?: Env::get('AWS_REGION')
            ?: Env::get('AWS_DEFAULT_REGION')
            ?: null;

        if ($region === null) {
            throw new RuntimeException('No AWS region available: provide one, or set AWS_ENV_SECRET_REGION, AWS_REGION, or AWS_DEFAULT_REGION.');
        }

        $config = [
            'version' => 'latest',
            'region' => $region,
            'http' => ['connect_timeout' => 5, 'timeout' => 15],
        ];

        $key = ($input['access_key_id'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_ACCESS_KEY_ID')
            ?: Env::get('AWS_ACCESS_KEY_ID')
            ?: null;
        $secret = ($input['secret_access_key'] ?? '')
            ?: Env::get('AWS_ENV_SECRET_SECRET_ACCESS_KEY')
            ?: Env::get('AWS_SECRET_ACCESS_KEY')
            ?: null;

        if ($key !== null && $secret !== null) {
            $config['credentials'] = ['key' => $key, 'secret' => $secret];
        }

        return new SecretsManagerClient($config);
    }
}
