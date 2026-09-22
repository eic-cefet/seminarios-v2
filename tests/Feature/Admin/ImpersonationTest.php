<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    $this->withHeader('Origin', 'http://localhost');
});

it('starts a browser session as the selected user and records the administrator', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin, 'web')->withSession(['unrelated' => 'discard']);
    $oldSession = session()->getId();

    $this->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertOk()->assertJsonPath('user.id', $target->id);

    $this->assertAuthenticatedAs($target, 'web');
    expect(session('impersonation.admin_id'))->toBe($admin->id)
        ->and(session('impersonation.user_id'))->toBe($target->id)
        ->and(session()->getId())->not->toBe($oldSession)
        ->and(session('unrelated'))->toBeNull();
    expect(AuditLog::where('event_name', 'admin.impersonation_started')->first())
        ->user_id->toBe($admin->id)->auditable_id->toBe($target->id);

    Auth::forgetGuards();
    $this->getJson('/api/auth/me')->assertOk()
        ->assertJsonPath('user.id', $target->id)
        ->assertJsonPath('user.is_impersonating', true);
    $this->getJson('/api/admin/users')->assertForbidden();
});

it('allows impersonating a teacher', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->teacher()->create();
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertOk()->assertJsonPath('user.id', $target->id);
});

it('rejects non-administrators', function (string $role) {
    $actor = $role === 'teacher' ? User::factory()->teacher()->create() : User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($actor, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertForbidden();
    $this->assertAuthenticatedAs($actor, 'web');
})->with(['user', 'teacher']);

it('requires authentication to start impersonation', function () {
    $target = User::factory()->create();
    $this->postJson("/api/admin/users/{$target->id}/impersonate")->assertUnauthorized();
});

it('rejects self and other administrators', function (bool $self) {
    $admin = User::factory()->admin()->create();
    $target = $self ? $admin : User::factory()->admin()->create();
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertForbidden();
})->with([true, false]);

it('rejects deleted and anonymized targets', function (bool $deleted) {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    if ($deleted) {
        $target->delete();
    } else {
        $target->forceFill(['anonymized_at' => now()])->save();
    }
    $response = $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate");
    $deleted ? $response->assertNotFound() : $response->assertForbidden();
})->with([true, false]);

it('rejects nested impersonation', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin, 'web')->withSession(['impersonation' => ['admin_id' => 999, 'user_id' => $admin->id]])
        ->postJson("/api/admin/users/{$target->id}/impersonate")->assertForbidden();
});

it('rejects token authentication without a browser session', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $token = $admin->createToken('test')->plainTextToken;
    $this->withToken($token)->postJson("/api/admin/users/{$target->id}/impersonate")->assertForbidden();
});

it('restores only the original administrator and rotates the session', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($target, 'web')->withSession(['impersonation' => ['admin_id' => $admin->id, 'credential_fingerprint' => hash_hmac('sha256', $admin->getAuthPassword(), config('app.key')), 'user_id' => $target->id]]);
    $oldSession = session()->getId();
    $this->postJson('/api/auth/impersonation/stop', ['admin_id' => 999])->assertOk()->assertJsonPath('user.id', $admin->id);
    $this->assertAuthenticatedAs($admin, 'web');
    expect(session('impersonation'))->toBeNull()->and(session()->getId())->not->toBe($oldSession);
    expect(AuditLog::where('event_name', 'admin.impersonation_stopped')->first())
        ->user_id->toBe($admin->id)->auditable_id->toBe($target->id);
});

it('rejects stopping without a matching impersonation session', function () {
    $target = User::factory()->create();
    $this->actingAs($target, 'web')->postJson('/api/auth/impersonation/stop')->assertForbidden();
    $admin = User::factory()->admin()->create();
    $this->withSession(['impersonation' => ['admin_id' => $admin->id, 'credential_fingerprint' => hash_hmac('sha256', $admin->getAuthPassword(), config('app.key')), 'user_id' => $target->id + 1]])
        ->postJson('/api/auth/impersonation/stop')->assertForbidden();
});

it('does not restore a deleted or demoted administrator', function (bool $deleted) {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    if ($deleted) {
        $admin->delete();
    } else {
        $admin->syncRoles([]);
    }
    $this->actingAs($target, 'web')->withSession(['impersonation' => ['admin_id' => $admin->id, 'credential_fingerprint' => hash_hmac('sha256', $admin->getAuthPassword(), config('app.key')), 'user_id' => $target->id]])
        ->postJson('/api/auth/impersonation/stop')->assertForbidden();
    $this->assertGuest('web');
    expect(session('impersonation'))->toBeNull();
})->with([true, false]);

it('attributes actions taken during impersonation to the original administrator', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($target, 'web')->withSession(['impersonation' => ['admin_id' => $admin->id, 'credential_fingerprint' => hash_hmac('sha256', $admin->getAuthPassword(), config('app.key')), 'user_id' => $target->id]]);
    $this->putJson('/api/profile', ['name' => 'Updated name', 'email' => $target->email])->assertOk();
    $log = AuditLog::where('event_name', 'user.updated')->latest('id')->firstOrFail();
    expect($log->user_id)->toBe($target->id)->and($log->event_data['impersonator_id'])->toBe($admin->id);
});

it('clears impersonation when logging out', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($target, 'web')->withSession(['impersonation' => ['admin_id' => $admin->id, 'credential_fingerprint' => hash_hmac('sha256', $admin->getAuthPassword(), config('app.key')), 'user_id' => $target->id]])
        ->postJson('/api/auth/logout')->assertOk();
    $this->assertGuest('web');
    expect(session('impersonation'))->toBeNull();
    expect(AuditLog::where('event_name', 'user.logout')->firstOrFail()->event_data['impersonator_id'])->toBe($admin->id);
    $this->postJson('/api/auth/impersonation/stop')->assertForbidden();
});

it('can return after the impersonated user is deleted', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertOk();
    $target->delete();
    Auth::forgetGuards();
    $this->postJson('/api/auth/impersonation/stop')->assertOk()->assertJsonPath('user.id', $admin->id);
});

it('expires the remember cookie without changing either account remember token', function () {
    $admin = User::factory()->admin()->create(['remember_token' => 'admin-token']);
    $target = User::factory()->create(['remember_token' => 'target-token']);
    $response = $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate");
    $response->assertOk()->assertCookieExpired(Auth::guard('web')->getRecallerName());
    expect($admin->fresh()->remember_token)->toBe('admin-token')
        ->and($target->fresh()->remember_token)->toBe('target-token');
});

it('preserves pending account deletion while switching identities', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create(['anonymization_requested_at' => now()]);
    $target = User::factory()->create(['anonymization_requested_at' => now()]);
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertOk();
    expect($target->fresh()->anonymization_requested_at)->not->toBeNull();
    $this->postJson('/api/auth/impersonation/stop')->assertOk();
    expect($admin->fresh()->anonymization_requested_at)->not->toBeNull();
    Mail::assertNothingQueued();
});

it('does not restore an administrator whose password changed during impersonation', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertOk();
    $admin->forceFill(['password' => 'changed-password'])->save();
    $this->postJson('/api/auth/impersonation/stop')->assertForbidden();
    $this->assertGuest('web');
});

it('does not revoke the target remember token when logging out of impersonation', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['remember_token' => 'target-remember-token']);
    $this->actingAs($admin, 'web')->postJson("/api/admin/users/{$target->id}/impersonate")->assertOk();
    $this->postJson('/api/auth/logout')->assertOk();
    expect($target->fresh()->remember_token)->toBe('target-remember-token');
});
