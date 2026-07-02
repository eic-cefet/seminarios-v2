<?php

use Symfony\Component\Process\Process;

function runLoadEnvSecretsShell(string $phpStubBody, string $secretId, string $probeCommand, array $extraEnv = []): Process
{
    $stubDir = sys_get_temp_dir().'/load-env-secrets-stub-'.uniqid();
    mkdir($stubDir, 0755, true);
    file_put_contents($stubDir.'/php', "#!/bin/bash\n".$phpStubBody."\n");
    chmod($stubDir.'/php', 0755);

    $helper = base_path('docker/load-env-secrets.sh');
    $process = new Process(
        ['bash', '-c', "source '{$helper}' && {$probeCommand}"],
        base_path(),
        array_merge([
            'PATH' => $stubDir.':'.getenv('PATH'),
            'AWS_ENV_SECRET_ID' => $secretId,
        ], $extraEnv),
    );
    $process->run();

    return $process;
}

it('does nothing when AWS_ENV_SECRET_ID is empty', function () {
    $process = runLoadEnvSecretsShell('exit 1', '', 'printf %s "OK:${FOO}"');

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('OK:');
});

it('evals the exports printed by the loader into the environment', function () {
    $process = runLoadEnvSecretsShell(
        "echo \"export FOO='from-secret'\"",
        'my-secret',
        'printf %s "OK:${FOO}"',
    );

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('OK:from-secret');
});

it('overrides an env var that already exists', function () {
    $process = runLoadEnvSecretsShell(
        "echo \"export FOO='overridden'\"",
        'my-secret',
        'printf %s "OK:${FOO}"',
        ['FOO' => 'original'],
    );

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toBe('OK:overridden');
});

it('fails when the loader script fails', function () {
    $process = runLoadEnvSecretsShell('echo "boom" >&2; exit 1', 'my-secret', 'printf %s "REACHED"');

    expect($process->getExitCode())->not->toBe(0)
        ->and($process->getOutput())->not->toContain('REACHED');
});
