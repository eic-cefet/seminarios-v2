<?php

use App\Services\SystemInfoService;

beforeEach(function () {
    $this->service = new SystemInfoService;
});

it('returns the runtime block with PHP and Laravel versions', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('runtime');
    expect($info['runtime'])->toMatchArray([
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => config('app.env'),
        'debug' => config('app.debug'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
    ]);
});

it('returns the server block with OS and hostname', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('server');
    expect($info['server'])->toHaveKeys([
        'os_family',
        'os_release',
        'hostname',
        'server_software',
        'sapi',
        'architecture',
    ]);
    expect($info['server']['os_family'])->toBe(PHP_OS_FAMILY);
    expect($info['server']['sapi'])->toBe(PHP_SAPI);
});

it('returns the memory block with limit and current usage in bytes', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('memory');
    expect($info['memory'])->toHaveKeys([
        'limit_bytes',
        'current_bytes',
        'peak_bytes',
    ]);
    expect($info['memory']['current_bytes'])->toBeInt()->toBeGreaterThan(0);
    expect($info['memory']['peak_bytes'])->toBeInt()->toBeGreaterThanOrEqual($info['memory']['current_bytes']);
});

it('returns the database block with driver, name and version', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('database');
    expect($info['database'])->toHaveKeys([
        'driver',
        'database',
        'host',
        'version',
    ]);
    expect($info['database']['driver'])->toBe(config('database.default'));
    expect($info['database']['version'])->toBeString()->not->toBeEmpty();
});

it('returns the cache block with cache, queue, session and mail drivers', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('drivers');
    expect($info['drivers'])->toMatchArray([
        'cache' => config('cache.default'),
        'queue' => config('queue.default'),
        'session' => config('session.driver'),
        'mail' => config('mail.default'),
        'filesystem' => config('filesystems.default'),
    ]);
});

it('returns the storage block with free and total bytes for the storage path', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('storage');
    expect($info['storage'])->toHaveKeys(['path', 'free_bytes', 'total_bytes']);
    expect($info['storage']['path'])->toBe(storage_path());
    expect($info['storage']['total_bytes'])->toBeInt()->toBeGreaterThan(0);
    expect($info['storage']['free_bytes'])->toBeInt()->toBeLessThanOrEqual($info['storage']['total_bytes']);
});

it('returns a sorted list of loaded PHP extensions', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('extensions');
    expect($info['extensions'])->toBeArray()->not->toBeEmpty();

    $sorted = $info['extensions'];
    sort($sorted);
    expect($info['extensions'])->toBe($sorted);
});

it('returns the scheduled tasks block with command, cron, timezone and next run', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('scheduler');
    expect($info['scheduler'])->toBeArray()->not->toBeEmpty();

    $first = $info['scheduler'][0];
    expect($first)->toHaveKeys([
        'command',
        'description',
        'expression',
        'timezone',
        'without_overlapping',
        'on_one_server',
        'next_run_at',
    ]);
    expect($first['expression'])->toBeString()->not->toBeEmpty();
});

it('returns PHP config values with parsed byte sizes', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('php_config');
    expect($info['php_config'])->toHaveKeys([
        'max_execution_time',
        'post_max_size',
        'upload_max_filesize',
        'opcache_enabled',
    ]);
    expect($info['php_config']['opcache_enabled'])->toBeBool();
});

it('never exposes secrets like the app key or db password', function () {
    $info = $this->service->collect();
    $flat = json_encode($info);

    expect($flat)->not->toContain((string) config('app.key'));
    expect($info['database'])->not->toHaveKey('password');
    expect($info['database'])->not->toHaveKey('username');
    expect($info)->not->toHaveKey('env');
});
