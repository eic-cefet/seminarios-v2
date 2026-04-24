<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

it('does not store the email address in forgot-password audit data', function () {
    Notification::fake();
    User::factory()->create(['email' => 'target@example.com']);

    postJson('/api/auth/forgot-password', ['email' => 'target@example.com']);

    $log = AuditLog::where('event_name', AuditEvent::UserForgotPassword->value)->first();

    expect($log)->not->toBeNull();
    $json = json_encode($log->event_data);
    expect($json)->not->toContain('target@example.com');
});

it('does not store the email address in reset-password audit data', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'target@example.com']);

    $token = app('auth.password.broker')->createToken($user);

    postJson('/api/auth/reset-password', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ]);

    $log = AuditLog::where('event_name', AuditEvent::UserPasswordReset->value)->first();

    expect($log)->not->toBeNull();
    $json = json_encode($log->event_data);
    expect($json)->not->toContain('target@example.com');
});
