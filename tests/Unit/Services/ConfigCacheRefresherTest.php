<?php

use App\Services\ConfigCacheRefresher;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->refresherEnvBackup = [];
    foreach (ConfigCacheRefresher::MANAGED_ENV_KEYS as $key) {
        $this->refresherEnvBackup[$key] = $_ENV[$key] ?? null;
    }
});

afterEach(function () {
    foreach (ConfigCacheRefresher::MANAGED_ENV_KEYS as $key) {
        $value = $this->refresherEnvBackup[$key];
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        } else {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
});

it('re-caches configuration and signals a queue restart, in that order', function () {
    Artisan::shouldReceive('call')->once()->ordered()->with('config:cache');
    Artisan::shouldReceive('call')->once()->ordered()->with('queue:restart');

    (new ConfigCacheRefresher)->refresh();
});

it('clears the managed env keys before re-caching so the fresh boot re-reads .env', function () {
    $_ENV['AWS_ENV_SECRET_ID'] = $_SERVER['AWS_ENV_SECRET_ID'] = 'stale-secret';
    putenv('AWS_ENV_SECRET_ID=stale-secret');

    Artisan::shouldReceive('call')->once()->with('config:cache')->andReturnUsing(function (): int {
        expect($_ENV)->not->toHaveKey('AWS_ENV_SECRET_ID')
            ->and($_SERVER)->not->toHaveKey('AWS_ENV_SECRET_ID')
            ->and(getenv('AWS_ENV_SECRET_ID'))->toBeFalse();

        return 0;
    });
    Artisan::shouldReceive('call')->once()->with('queue:restart');

    (new ConfigCacheRefresher)->refresh();
});
