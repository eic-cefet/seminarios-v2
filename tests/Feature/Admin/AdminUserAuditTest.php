<?php

use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;

beforeEach(fn () => actingAsAdmin());

it('audits role assignment on user creation', function () {
    $this->postJson('/api/admin/users', [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'JanesPass#2026',
        'role' => Role::Teacher->value,
    ])->assertCreated();

    $log = AuditLog::where('event_name', AuditEvent::UserRoleAssigned->value)->first();
    expect($log)->not->toBeNull()
        ->and($log->event_data['role'])->toBe(Role::Teacher->value)
        ->and($log->event_data['source'])->toBe('store');
});

it('audits role change on update', function () {
    $user = User::factory()->teacher()->create();

    $this->putJson("/api/admin/users/{$user->id}", [
        'role' => Role::Admin->value,
    ])->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::UserRoleAssigned->value)->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('audits role removal when user demoted to plain user', function () {
    $user = User::factory()->teacher()->create();

    $this->putJson("/api/admin/users/{$user->id}", [
        'role' => 'user',
    ])->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::UserRoleRevoked->value)->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('audits admin-initiated password reset', function () {
    $user = User::factory()->create();

    $this->putJson("/api/admin/users/{$user->id}", [
        'password' => 'AdminSet#2026',
    ])->assertSuccessful();

    $log = AuditLog::where('event_name', AuditEvent::UserPasswordResetByAdmin->value)->first();
    expect($log)->not->toBeNull()
        ->and($log->auditable_id)->toBe($user->id);
});

it('audits admin viewing a user detail', function () {
    $user = User::factory()->create();

    $this->getJson("/api/admin/users/{$user->id}")->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::UserViewedByAdmin->value)->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('audits admin deleting a user', function () {
    $user = User::factory()->create();

    $this->deleteJson("/api/admin/users/{$user->id}")->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::UserDeletedByAdmin->value)->where('auditable_id', $user->id)->exists())->toBeTrue();
});

it('audits admin restoring a user', function () {
    $user = User::factory()->create();
    $user->delete();

    $this->postJson("/api/admin/users/{$user->id}/restore")->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::UserRestoredByAdmin->value)->where('auditable_id', $user->id)->exists())->toBeTrue();
});
