<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnforceIdempotency;
use App\Models\SeminarLocation;
use App\Models\User;
use App\Support\External\IdempotencyStore;
use App\Support\Locking\LockKey;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @return array{0: User, 1: string}
 */
function makeAdminWithToken(): array
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test', ['*'])->plainTextToken;

    return [$admin, $token];
}

it('replays the original response on a retry with the same key and body', function () {
    [, $token] = makeAdminWithToken();
    $body = ['name' => 'Sala Idempotente', 'max_vacancies' => 50];

    $first = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $first->assertCreated();

    $second = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $second->assertStatus($first->getStatusCode());
    expect($second->getContent())->toBe($first->getContent());
    expect(SeminarLocation::where('name', 'Sala Idempotente')->count())->toBe(1);
});

it('returns 409 on retry with same key but different body', function () {
    [, $token] = makeAdminWithToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/external/v1/locations', ['name' => 'A', 'max_vacancies' => 10], ['Idempotency-Key' => 'k-2'])
        ->assertCreated();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/external/v1/locations', ['name' => 'B', 'max_vacancies' => 20], ['Idempotency-Key' => 'k-2'])
        ->assertStatus(409)
        ->assertJsonPath('error', 'idempotency_key_conflict');
});

it('rejects malformed Idempotency-Key', function () {
    [, $token] = makeAdminWithToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/external/v1/locations', [], ['Idempotency-Key' => str_repeat('x', 300)])
        ->assertStatus(422)
        ->assertJsonPath('error', 'validation_error');
});

it('scopes the cache key and lock key with the same hash', function () {
    $tokenScope = 'abc';
    $key = 'shared-key';

    $cacheKey = IdempotencyStore::cacheKey($tokenScope, $key);
    $lockKey = LockKey::externalIdempotency($tokenScope, $key);
    $hash = hash('sha256', $key);

    expect($cacheKey)->toBe('external_api:idempotency:'.$tokenScope.':'.$hash);
    expect($lockKey)->toBe('lock:external_api:idempotency:'.$tokenScope.':'.$hash);
});

it('acquires and releases a cache lock around the idempotent handler', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(5)->andReturnTrue();
    $lock->shouldReceive('release')->once()->andReturnTrue();

    Cache::shouldReceive('lock')
        ->once()
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'lock:external_api:idempotency:') && $sec === 10)
        ->andReturn($lock);
    Cache::shouldReceive('get')->andReturn(null);
    Cache::shouldReceive('put')->andReturnTrue();

    [, $token] = makeAdminWithToken();
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(
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
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'lock:external_api:idempotency:') && $sec === 10)
        ->andReturn($lock);

    [, $token] = makeAdminWithToken();
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(
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
        ->withArgs(fn (string $key, int $sec) => str_starts_with($key, 'lock:external_api:idempotency:') && $sec === 10)
        ->andReturn($lock);
    Cache::shouldReceive('get')->andReturn(null);

    [, $token] = makeAdminWithToken();
    // Missing required fields to force a validation error inside the handler.
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(
        '/api/external/v1/locations',
        [],
        ['Idempotency-Key' => 'lk-3']
    )->assertStatus(422);
});

it('does not collide across users that happen to share a token id', function () {
    $userA = User::factory()->create();
    $userA->assignRole('admin');
    $userB = User::factory()->create();
    $userB->assignRole('admin');
    $tokenA = $userA->createToken('a', ['*'])->plainTextToken;
    $tokenB = $userB->createToken('b', ['*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->postJson('/api/external/v1/locations', ['name' => 'Shared Key Test', 'max_vacancies' => 5], ['Idempotency-Key' => 'shared-key'])
        ->assertCreated();

    // Flush the cached auth user so the second request re-resolves Sanctum
    // from the new Authorization header instead of reusing the first guard's cached user.
    auth()->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->postJson(
            '/api/external/v1/locations',
            ['name' => 'User B Location', 'max_vacancies' => 7],
            ['Idempotency-Key' => 'shared-key']
        )->assertCreated();

    expect(SeminarLocation::whereIn('name', ['Shared Key Test', 'User B Location'])->count())->toBe(2);
});

it('throws unauthenticated when the request has no token', function () {
    $store = app(IdempotencyStore::class);
    $middleware = new EnforceIdempotency($store);

    $request = Request::create('/api/external/v1/locations', 'POST', [], [], [], [], json_encode(['x' => 1]));
    $request->headers->set('Idempotency-Key', 'no-auth');

    $middleware->handle($request, fn () => response('ok'));
})->throws(ApiException::class, 'Não autenticado');
