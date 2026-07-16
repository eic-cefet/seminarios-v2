<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Locking\LockKey;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;

beforeEach(function () {
    $this->originalEnvironment = app()->environment();
    config(['features.database_reset.enabled' => true]);
});

afterEach(function () {
    app()->detectEnvironment(fn () => $this->originalEnvironment);
});

it('requires authentication', function () {
    Artisan::shouldReceive('call')->never();

    $this->postJson('/api/admin/system/database/reset', [
        'confirmation' => 'APAGAR BANCO',
    ])->assertUnauthorized();
});

it('rejects regular users and teachers', function (string $role) {
    Artisan::shouldReceive('call')->never();
    $user = $role === 'teacher'
        ? User::factory()->teacher()->create()
        : User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertForbidden();
})->with(['user', 'teacher']);

it('requires the exact confirmation phrase', function (?string $confirmation) {
    Artisan::shouldReceive('call')->never();
    $admin = User::factory()->admin()->create();

    $payload = $confirmation === null ? [] : ['confirmation' => $confirmation];

    $this->actingAs($admin)
        ->postJson('/api/admin/system/database/reset', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirmation');
})->with([null, 'apagar banco', 'APAGAR']);

it('rejects the reset when the feature is disabled', function () {
    Artisan::shouldReceive('call')->never();
    config(['features.database_reset.enabled' => false]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertForbidden();
});

it('rejects production even when the feature is enabled', function () {
    Artisan::shouldReceive('call')->never();
    app()->detectEnvironment(fn () => 'production');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertForbidden();
});

it('returns conflict while another reset holds the lock', function () {
    Artisan::shouldReceive('call')->never();
    $admin = User::factory()->admin()->create();
    $lock = Cache::store('file')->lock(LockKey::databaseReset(), 600);
    expect($lock->get())->toBeTrue();

    try {
        $this->actingAs($admin)
            ->postJson('/api/admin/system/database/reset', [
                'confirmation' => 'APAGAR BANCO',
            ])
            ->assertConflict();
    } finally {
        $lock->release();
    }
});

it('resets, audits, invalidates the session, and returns success', function () {
    $admin = User::factory()->admin()->create();
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--seed' => true, '--force' => true])
        ->andReturn(0);

    $this->actingAs($admin)->withSession(['session-probe' => true]);
    $previousSessionId = session()->getId();
    $previousCsrfToken = session()->token();

    $this
        ->withHeader('Origin', 'http://localhost')
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Banco de dados recriado e populado com sucesso.');

    $this->assertGuest();

    $audit = AuditLog::forEvent(AuditEvent::DatabaseReset)->firstOrFail();
    expect($audit->event_data)->toMatchArray([
        'environment' => app()->environment(),
        'triggered_by_user_id' => $admin->id,
        'triggered_by_email' => $admin->email,
    ])
        ->and(session()->getId())->not->toBe($previousSessionId)
        ->and(session()->token())->not->toBe($previousCsrfToken);
});

it('does not persist the stale administrator while logging out after reset', function () {
    $rememberToken = 'remembered-before-reset';
    $admin = User::factory()->admin()->create(['remember_token' => $rememberToken]);
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--seed' => true, '--force' => true])
        ->andReturn(0);

    $this->actingAs($admin)
        ->withHeader('Origin', 'http://localhost')
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertSuccessful();

    expect(User::query()->findOrFail($admin->id)->remember_token)->toBe($rememberToken);
    $this->assertGuest();
});

it('reports audit failures without changing the successful reset response', function () {
    $admin = User::factory()->admin()->create();
    $auditException = new RuntimeException('Audit storage is unavailable.');
    Exceptions::fake();
    AuditLog::creating(function () use ($auditException): void {
        throw $auditException;
    });
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', ['--seed' => true, '--force' => true])
        ->andReturn(0);

    $this->actingAs($admin)
        ->withHeader('Origin', 'http://localhost')
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Banco de dados recriado e populado com sucesso.');

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $auditException);
    $this->assertGuest();
});

it('returns server error and keeps the session when artisan fails', function () {
    $admin = User::factory()->admin()->create();
    Artisan::shouldReceive('call')->once()->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('Seeder failed');

    $this->actingAs($admin)
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertServerError();

    $this->assertAuthenticated();
    expect(AuditLog::forEvent(AuditEvent::DatabaseReset)->exists())->toBeFalse();
});

it('returns server error when artisan throws', function () {
    $admin = User::factory()->admin()->create();
    Artisan::shouldReceive('call')->once()->andThrow(new RuntimeException('boom'));

    $this->actingAs($admin)
        ->postJson('/api/admin/system/database/reset', [
            'confirmation' => 'APAGAR BANCO',
        ])
        ->assertServerError();
});
