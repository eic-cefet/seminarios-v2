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
    $defaultConnection = config('database.default');
    expect($info['database']['driver'])->toBe(config("database.connections.{$defaultConnection}.driver"));
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

it('returns the storage block with only free and total bytes (no path)', function () {
    $info = $this->service->collect();

    expect($info)->toHaveKey('storage');
    expect($info['storage'])->toHaveKeys(['free_bytes', 'total_bytes']);
    expect($info['storage'])->not->toHaveKey('path');
    expect($info['storage']['total_bytes'])->toBeInt()->toBeGreaterThan(0);
    expect($info['storage']['free_bytes'])->toBeInt()->toBeLessThanOrEqual($info['storage']['total_bytes']);
});

it('does not leak the absolute storage path anywhere in the payload', function () {
    $info = $this->service->collect();
    $flat = json_encode($info);

    expect($flat)->not->toContain(storage_path());
});

it('reads the driver from connections.*.driver, not from the default connection name', function () {
    $defaultConnection = config('database.default');
    $expectedDriver = config("database.connections.{$defaultConnection}.driver");

    $info = $this->service->collect();

    expect($info['database']['driver'])->toBe($expectedDriver);
    // Sanity check: with our config the connection name might differ from the driver
    // The point is we expose the driver string, never the connection name verbatim.
    expect($info['database']['driver'])->toBeIn(['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv']);
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

it('parses gigabyte memory limits', function () {
    $original = ini_get('memory_limit');
    ini_set('memory_limit', '2G');

    try {
        $info = $this->service->collect();
        expect($info['memory']['limit_bytes'])->toBe(2 * 1024 * 1024 * 1024);
    } finally {
        ini_set('memory_limit', (string) $original);
    }
});

it('parses kilobyte memory limits', function () {
    $original = ini_get('memory_limit');
    // Must be above current process usage; 512000K ≈ 500MB
    ini_set('memory_limit', '512000K');

    try {
        $info = $this->service->collect();
        expect($info['memory']['limit_bytes'])->toBe(512000 * 1024);
    } finally {
        ini_set('memory_limit', (string) $original);
    }
});

it('treats unlimited memory_limit (-1) as -1 bytes', function () {
    $original = ini_get('memory_limit');
    ini_set('memory_limit', '-1');

    try {
        $info = $this->service->collect();
        expect($info['memory']['limit_bytes'])->toBe(-1);
    } finally {
        ini_set('memory_limit', (string) $original);
    }
});

it('parses raw byte memory limits without unit suffix', function () {
    $original = ini_get('memory_limit');
    // 536870912 bytes = 512MB; above the test process's current usage
    ini_set('memory_limit', '536870912');

    try {
        $info = $this->service->collect();
        expect($info['memory']['limit_bytes'])->toBe(536870912);
    } finally {
        ini_set('memory_limit', (string) $original);
    }
});

it('never exposes secrets like the app key or db password', function () {
    $info = $this->service->collect();
    $flat = json_encode($info);

    expect($flat)->not->toContain((string) config('app.key'));
    expect($info['database'])->not->toHaveKey('password');
    expect($info['database'])->not->toHaveKey('username');
    expect($info)->not->toHaveKey('env');
});
