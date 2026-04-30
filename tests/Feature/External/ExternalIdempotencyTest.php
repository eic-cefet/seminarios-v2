<?php

use App\Models\SeminarLocation;
use App\Support\External\IdempotencyStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

it('replays the original response on a retry with the same key and body', function () {
    actingAsAdmin();
    $body = ['name' => 'Sala Idempotente', 'max_vacancies' => 50];

    $first = $this->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $first->assertCreated();

    $second = $this->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $second->assertStatus($first->getStatusCode());
    expect($second->getContent())->toBe($first->getContent());
    expect(SeminarLocation::where('name', 'Sala Idempotente')->count())->toBe(1);
});

it('returns 409 on retry with same key but different body', function () {
    actingAsAdmin();
    $this->postJson('/api/external/v1/locations', ['name' => 'A', 'max_vacancies' => 10], ['Idempotency-Key' => 'k-2'])->assertCreated();

    $this->postJson('/api/external/v1/locations', ['name' => 'B', 'max_vacancies' => 20], ['Idempotency-Key' => 'k-2'])
        ->assertStatus(409)
        ->assertJsonPath('error', 'idempotency_key_conflict');
});

it('rejects malformed Idempotency-Key', function () {
    actingAsAdmin();
    $this->postJson('/api/external/v1/locations', [], ['Idempotency-Key' => str_repeat('x', 300)])
        ->assertStatus(422)
        ->assertJsonPath('error', 'validation_error');
});

it('scopes the cache key and lock key with the same hash', function () {
    $tokenScope = 'abc';
    $key = 'shared-key';

    $cacheKey = IdempotencyStore::cacheKey($tokenScope, $key);
    $lockKey = IdempotencyStore::lockKey($tokenScope, $key);
    $hash = hash('sha256', $key);

    expect($cacheKey)->toBe('external_api:idempotency:'.$tokenScope.':'.$hash);
    expect($lockKey)->toBe('external_api:idempotency_lock:'.$tokenScope.':'.$hash);
});

it('acquires and releases a cache lock around the idempotent handler', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(5)->andReturnTrue();
    $lock->shouldReceive('release')->once()->andReturnTrue();

    Cache::shouldReceive('lock')
        ->once()
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'external_api:idempotency_lock:') && $sec === 10)
        ->andReturn($lock);
    Cache::shouldReceive('get')->andReturn(null);
    Cache::shouldReceive('put')->andReturnTrue();

    actingAsAdmin();
    $this->postJson(
        '/api/external/v1/locations',
        ['name' => 'Lock Test', 'max_vacancies' => 10],
        ['Idempotency-Key' => 'lk-1']
    )->assertCreated();
});

it('returns 409 when the lock cannot be acquired', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(5)->andThrow(new LockTimeoutException);
    $lock->shouldNotReceive('release');

    Cache::shouldReceive('lock')
        ->once()
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'external_api:idempotency_lock:') && $sec === 10)
        ->andReturn($lock);

    actingAsAdmin();
    $this->postJson(
        '/api/external/v1/locations',
        ['name' => 'Lock Timeout', 'max_vacancies' => 10],
        ['Idempotency-Key' => 'lk-2']
    )
        ->assertStatus(409)
        ->assertJsonPath('error', 'idempotency_key_conflict');
});

it('releases the lock even when the handler throws', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(5)->andReturnTrue();
    $lock->shouldReceive('release')->once()->andReturnTrue();

    Cache::shouldReceive('lock')
        ->once()
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'external_api:idempotency_lock:') && $sec === 10)
        ->andReturn($lock);
    Cache::shouldReceive('get')->andReturn(null);

    actingAsAdmin();
    // Missing required fields to force a validation error inside the handler.
    $this->postJson(
        '/api/external/v1/locations',
        [],
        ['Idempotency-Key' => 'lk-3']
    )->assertStatus(422);
});
