<?php

use App\Support\Locking\LockTimeoutException;
use App\Support\Locking\Mutex;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

it('runs the callback under the lock and returns its value', function () {
    expect(Mutex::for('test:key')->protect(fn () => 'ok'))->toBe('ok');
});

it('releases the lock when the callback throws', function () {
    try {
        Mutex::for('test:throw')->protect(fn () => throw new RuntimeException('boom'));
    } catch (RuntimeException) {
    }

    expect(Mutex::for('test:throw', waitSeconds: 1)->protect(fn () => 'free'))->toBe('free');
});

it('blocks contending acquirers and times out cleanly', function () {
    Cache::lock('test:contend', 60)->get();

    expect(fn () => Mutex::for('test:contend', waitSeconds: 1)->protect(fn () => 'never'))
        ->toThrow(LockTimeoutException::class);
});

it('tryProtect returns null when not acquired and the value otherwise', function () {
    expect(Mutex::for('test:try')->tryProtect(fn () => 'first'))->toBe('first');

    Cache::lock('test:try', 60)->get();
    expect(Mutex::for('test:try')->tryProtect(fn () => 'second'))->toBeNull();
});

it('swallows exceptions when releasing an already-expired lock', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->andReturnTrue();
    $lock->shouldReceive('release')->once()->andThrow(new RuntimeException('lock gone'));

    Cache::shouldReceive('lock')->once()->andReturn($lock);

    expect(Mutex::for('test:expired')->protect(fn () => 'value'))->toBe('value');
});
