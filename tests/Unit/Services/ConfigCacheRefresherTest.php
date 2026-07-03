<?php

use App\Services\ConfigCacheRefresher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

it('re-caches configuration in a subprocess, then signals a queue restart', function () {
    Process::fake();
    Artisan::shouldReceive('call')->once()->with('queue:restart');

    (new ConfigCacheRefresher)->refresh();

    Process::assertRan(function ($process) {
        return $process->path === base_path()
            && $process->command === [PHP_BINARY, 'artisan', 'config:cache'];
    });
});

it('throws and skips the queue restart when config:cache fails', function () {
    Process::fake(['*' => Process::result(exitCode: 1, errorOutput: 'boom')]);
    Artisan::shouldReceive('call')->never();

    expect(fn () => (new ConfigCacheRefresher)->refresh())
        ->toThrow(RuntimeException::class, 'config:cache failed: boom');
});

it('falls back to stdout in the failure message when stderr is empty', function () {
    Process::fake(['*' => Process::result(output: 'stdout says no', exitCode: 1)]);
    Artisan::shouldReceive('call')->never();

    expect(fn () => (new ConfigCacheRefresher)->refresh())
        ->toThrow(RuntimeException::class, 'config:cache failed: stdout says no');
});
