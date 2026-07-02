<?php

/**
 * Prints `export KEY='value'` lines for every entry in the AWS Secrets Manager
 * secret named by AWS_ENV_SECRET_ID. Sourced-and-eval'd by
 * docker/load-env-secrets.sh before each service starts.
 *
 * Intentionally does NOT boot Laravel: it must run before config:cache and
 * must not depend on env values that the secret itself may provide.
 */
ini_set('display_errors', 'stderr');

require __DIR__.'/../vendor/autoload.php';

use App\Services\AwsSecretEnvService;
use Aws\SecretsManager\SecretsManagerClient;

$secretId = getenv('AWS_ENV_SECRET_ID') ?: null;

if ($secretId === null) {
    exit(0);
}

$region = getenv('AWS_ENV_SECRET_REGION')
    ?: getenv('AWS_REGION')
    ?: getenv('AWS_DEFAULT_REGION')
    ?: null;

if ($region === null) {
    fwrite(STDERR, "load-env-secrets: AWS_ENV_SECRET_ID is set but no AWS region is available (set AWS_ENV_SECRET_REGION, AWS_REGION, or AWS_DEFAULT_REGION).\n");
    exit(1);
}

try {
    $service = new AwsSecretEnvService(new SecretsManagerClient([
        'version' => 'latest',
        'region' => $region,
    ]));

    echo $service->toShellExports($service->fetchEnvVars($secretId));
} catch (Throwable $e) {
    fwrite(STDERR, "load-env-secrets: failed to load secret '{$secretId}': {$e->getMessage()}\n");
    exit(1);
}
