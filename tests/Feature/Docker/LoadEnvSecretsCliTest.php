<?php

use Symfony\Component\Process\Process;

function runLoadEnvSecretsCli(array $env): Process
{
    $process = new Process(
        [PHP_BINARY, base_path('docker/load-env-secrets.php')],
        base_path(),
        array_merge([
            'AWS_ENV_SECRET_ID' => '',
            'AWS_ENV_SECRET_REGION' => '',
            'AWS_REGION' => '',
            'AWS_DEFAULT_REGION' => '',
        ], $env),
    );
    $process->run();

    return $process;
}

it('exits 0 with no output when AWS_ENV_SECRET_ID is not set', function () {
    $process = runLoadEnvSecretsCli([]);

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('');
});

it('exits 1 with an error when the secret id is set but no region is available', function () {
    $process = runLoadEnvSecretsCli(['AWS_ENV_SECRET_ID' => 'my-secret']);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getOutput())->toBe('')
        ->and($process->getErrorOutput())->toContain('AWS region');
});
