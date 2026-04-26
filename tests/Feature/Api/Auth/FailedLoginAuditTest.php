<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;

it('records audit log when login fails for unknown email', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'ghost@example.com',
        'password' => 'whatever',
    ])->assertUnauthorized();

    $log = AuditLog::where('event_name', AuditEvent::UserLoginFailed->value)->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBeNull()
        ->and($log->event_data['reason'])->toBe('user_not_found')
        ->and($log->event_data)->not->toHaveKey('email'); // no PII
});

it('records audit log when login fails for wrong password', function () {
    $user = User::factory()->create(['password' => 'CorrectHorse#2026']);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();

    $log = AuditLog::where('event_name', AuditEvent::UserLoginFailed->value)->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->event_data['reason'])->toBe('invalid_password');
});
