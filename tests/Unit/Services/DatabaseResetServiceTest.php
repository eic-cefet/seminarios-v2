<?php

use App\Exceptions\ApiException;
use App\Services\DatabaseResetService;
use App\Support\Locking\LockKey;
use App\Support\Locking\LockTimeoutException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->originalEnvironment = app()->environment();
    config(['features.database_reset.enabled' => true]);
});

afterEach(function () {
    app()->detectEnvironment(fn () => $this->originalEnvironment);
});

it('is unavailable when the feature flag is disabled', function () {
    config(['features.database_reset.enabled' => false]);

    expect(app(DatabaseResetService::class)->isAvailable())->toBeFalse();
});

it('is unavailable in production even when the feature flag is enabled', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(app(DatabaseResetService::class)->isAvailable())->toBeFalse();
});

it('is available in a non-production environment when enabled', function () {
    app()->detectEnvironment(fn () => 'sanduleak');

    expect(app(DatabaseResetService::class)->isAvailable())->toBeTrue();
});

it('refuses to reset when unavailable', function () {
    config(['features.database_reset.enabled' => false]);
    Artisan::shouldReceive('call')->never();

    expect(fn () => app(DatabaseResetService::class)->reset())
        ->toThrow(ApiException::class);
});

it('runs migrate fresh with seed and force synchronously', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--seed' => true, '--force' => true])
        ->andReturn(0);

    app(DatabaseResetService::class)->reset();
});

it('throws when artisan returns a nonzero exit code', function () {
    Artisan::shouldReceive('call')->once()->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('Seeder failed');

    expect(fn () => app(DatabaseResetService::class)->reset())
        ->toThrow(RuntimeException::class, 'Seeder failed');
});

it('rejects a concurrent reset through the file-store lock', function () {
    $lock = Cache::store('file')->lock(LockKey::databaseReset(), 600);
    expect($lock->get())->toBeTrue();

    try {
        expect(fn () => app(DatabaseResetService::class)->reset())
            ->toThrow(LockTimeoutException::class);
    } finally {
        $lock->release();
    }
});
