<?php

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\IpHasher;
use App\Services\UserAnonymizationService;

it('scrubs email addresses inside event_data.to arrays', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => AuditEvent::EmailSent->value,
        'event_type' => AuditEventType::System,
        'event_data' => [
            'mail' => 'WelcomeUser',
            'to' => ['jane@example.com'],
            'subject' => 'Bem-vindo',
        ],
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $log = AuditLog::where('event_name', AuditEvent::EmailSent->value)->first();
    expect($log->event_data['to'])->not->toContain('jane@example.com')
        ->and($log->event_data['mail'])->toBe('WelcomeUser');
});

it('scrubs top-level PII keys in manual event_data', function () {
    $user = User::factory()->create();

    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => 'something.custom',
        'event_type' => AuditEventType::Manual,
        'event_data' => [
            'note' => 'admin note',
            'email' => 'leak@example.com',
            'cpf' => '123.456.789-00',
        ],
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $log = AuditLog::where('event_name', 'something.custom')->first();
    expect($log->event_data['email'])->toBe('[scrubbed]')
        ->and($log->event_data['cpf'])->toBe('[scrubbed]')
        ->and($log->event_data['note'])->toBe('admin note');
});

it('scrubs PII even when auditable_type is not User', function () {
    $user = User::factory()->create();

    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => 'some.event',
        'event_type' => AuditEventType::Manual,
        'auditable_type' => 'App\\Models\\Registration',
        'auditable_id' => 99,
        'event_data' => ['old_values' => ['email' => 'x@y.com']],
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $log = AuditLog::where('event_name', 'some.event')->first();
    expect($log->event_data['old_values']['email'])->toBe('[scrubbed]');
});

it('leaves hashed email recipients untouched (production data shape)', function () {
    config(['app.key' => 'base64:dGVzdC1zYWx0LTIwMjY=']);

    $user = User::factory()->create(['email' => 'kate@example.com']);
    $hashedEmail = app(IpHasher::class)->hashOpaque($user->email);

    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => AuditEvent::EmailSent->value,
        'event_type' => AuditEventType::System,
        'event_data' => [
            'mail' => 'WelcomeUser',
            'to' => [$hashedEmail],
            'subject' => 'Bem-vindo',
        ],
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $log = AuditLog::where('event_name', AuditEvent::EmailSent->value)->first();
    // Hash is opaque w.r.t. the user's plaintext email, so the scrubber
    // leaves it in place — there's no PII to remove.
    expect($log->event_data['to'])->toBe([$hashedEmail]);
});

it('leaves audit logs with non-array event_data untouched', function () {
    $user = User::factory()->create();

    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => 'plain.event',
        'event_type' => AuditEventType::Manual,
        'event_data' => null,
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $log = AuditLog::where('event_name', 'plain.event')->first();
    expect($log->event_data)->toBeNull();
});
