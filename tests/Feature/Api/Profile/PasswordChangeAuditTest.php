<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;

it('audits successful password change', function () {
    $user = User::factory()->create(['password' => 'OldPassword#2026']);

    $this->actingAs($user)->putJson('/api/profile/password', [
        'current_password' => 'OldPassword#2026',
        'password' => 'NewPassword#2026',
        'password_confirmation' => 'NewPassword#2026',
    ])->assertSuccessful();

    $log = AuditLog::where('event_name', AuditEvent::UserPasswordChanged->value)->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->auditable_id)->toBe($user->id)
        ->and(json_encode($log->event_data ?? []))->not->toContain('password');
});

it('does not audit when current password is wrong', function () {
    $user = User::factory()->create(['password' => 'OldPassword#2026']);

    $this->actingAs($user)->putJson('/api/profile/password', [
        'current_password' => 'wrong',
        'password' => 'NewPassword#2026',
        'password_confirmation' => 'NewPassword#2026',
    ])->assertUnauthorized();

    expect(AuditLog::where('event_name', AuditEvent::UserPasswordChanged->value)->count())->toBe(0);
});
