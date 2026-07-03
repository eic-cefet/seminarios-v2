<?php

namespace App\Services;

use Aws\SecretsManager\SecretsManagerClient;
use RuntimeException;

/**
 * Fetches a JSON key/value secret from AWS Secrets Manager as a map of
 * environment variable names to values. Consumed by AwsSecretEnvLoader
 * during application bootstrap (before configuration is loaded) to
 * append/override the process environment.
 */
class AwsSecretEnvService
{
    private const ENV_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(private SecretsManagerClient $client) {}

    /**
     * @return array<string, string>
     */
    public function fetchEnvVars(string $secretId): array
    {
        $result = $this->client->getSecretValue(['SecretId' => $secretId]);
        $payload = $result['SecretString'] ?? null;

        if (! is_string($payload)) {
            throw new RuntimeException("Secret '{$secretId}' must contain a string payload (binary secrets are not supported).");
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new RuntimeException("Secret '{$secretId}' must be a JSON object mapping env names to values.");
        }

        $envVars = [];

        foreach ($decoded as $key => $value) {
            $key = (string) $key;

            if (preg_match(self::ENV_NAME_PATTERN, $key) !== 1) {
                throw new RuntimeException("Invalid environment variable name '{$key}' in secret '{$secretId}'.");
            }

            if (is_bool($value)) {
                $envVars[$key] = $value ? 'true' : 'false';
            } elseif (is_string($value) || is_int($value) || is_float($value)) {
                $envVars[$key] = (string) $value;
            } else {
                throw new RuntimeException("Value for '{$key}' in secret '{$secretId}' must be a string, number, or boolean.");
            }
        }

        return $envVars;
    }
}
