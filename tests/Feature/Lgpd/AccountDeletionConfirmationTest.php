<?php

use App\Enums\AuditEvent;
use App\Mail\AccountDeletionConfirmation;
use App\Mail\AccountDeletionScheduled;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

it('sends a confirmation email without marking the user', function () {
    Mail::fake();
    $user = actingAsUser();

    $response = $this->postJson('/api/profile/delete-request', ['password' => 'password']);

    $response->assertSuccessful();
    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionConfirmation::class, fn ($mail) => $mail->hasTo($user->email));
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionConfirmationSent->value)->exists())->toBeTrue();
});

it('confirms deletion via token and marks the user', function () {
    Mail::fake();
    $user = actingAsUser();

    $this->postJson('/api/profile/delete-request', ['password' => 'password'])->assertSuccessful();

    $sent = Mail::queued(AccountDeletionConfirmation::class)->first();
    expect($sent)->not->toBeNull();

    preg_match('#/confirmar-exclusao/([a-zA-Z0-9]{64})#', $sent->confirmUrl, $m);
    $token = $m[1] ?? null;
    expect($token)->not->toBeNull();

    $this->postJson('/api/profile/delete-confirm', ['token' => $token])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->not->toBeNull();
    Mail::assertQueued(AccountDeletionScheduled::class);
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionRequested->value)->exists())->toBeTrue();
});

it('rejects invalid or expired tokens', function () {
    actingAsUser();

    $this->postJson('/api/profile/delete-confirm', ['token' => str_repeat('x', 64)])
        ->assertUnprocessable();
});

it('rejects tokens belonging to another user', function () {
    Mail::fake();
    $alice = actingAsUser();

    $this->postJson('/api/profile/delete-request', ['password' => 'password']);
    $sent = Mail::queued(AccountDeletionConfirmation::class)->first();
    preg_match('#/confirmar-exclusao/([a-zA-Z0-9]{64})#', $sent->confirmUrl, $m);
    $token = $m[1];

    $bob = User::factory()->create();
    $this->actingAs($bob);

    $this->postJson('/api/profile/delete-confirm', ['token' => $token])->assertForbidden();
});

it('does not consume the token when a different user attempts to confirm', function () {
    Mail::fake();
    $alice = actingAsUser();

    $this->postJson('/api/profile/delete-request', ['password' => 'password'])->assertSuccessful();
    $sent = Mail::queued(AccountDeletionConfirmation::class)->first();
    preg_match('#/confirmar-exclusao/([a-zA-Z0-9]{64})#', $sent->confirmUrl, $m);
    $token = $m[1];

    // Bob tries to use Alice's token — should fail without consuming it.
    $bob = User::factory()->create();
    $this->actingAs($bob);
    $this->postJson('/api/profile/delete-confirm', ['token' => $token])->assertForbidden();

    // Alice can still confirm with the same token afterwards.
    $this->actingAs($alice);
    $this->postJson('/api/profile/delete-confirm', ['token' => $token])->assertSuccessful();
    expect($alice->fresh()->anonymization_requested_at)->not->toBeNull();
});

it('is idempotent when the user is already flagged for deletion', function () {
    Mail::fake();
    $user = actingAsUser();
    $user->forceFill(['anonymization_requested_at' => now()->subDay()])->save();

    Cache::put('lgpd.deletion-confirm:'.str_repeat('a', 64), $user->id, now()->addHour());

    $response = $this->postJson('/api/profile/delete-confirm', ['token' => str_repeat('a', 64)]);
    $response->assertSuccessful();
    expect($response->json('message'))->toContain('já foi confirmada');

    // Token was consumed; second call must fail
    $this->postJson('/api/profile/delete-confirm', ['token' => str_repeat('a', 64)])->assertUnprocessable();
});

it('returns 401 when unauthenticated on delete-confirm', function () {
    $this->postJson('/api/profile/delete-confirm', ['token' => str_repeat('z', 64)])
        ->assertUnauthorized();
});

it('validates token size on delete-confirm', function () {
    actingAsUser();

    $this->postJson('/api/profile/delete-confirm', ['token' => 'short'])
        ->assertUnprocessable();
});
