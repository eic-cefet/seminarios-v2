<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('features.notification_audit.enabled', true);
    // Run notifications synchronously so the listener fires within the test.
    Queue::fake();
});

it('writes an AuditLog row when an in-app notification is sent', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $user->notifyNow(new CertificateReadyNotification($seminar, '/go'));

    expect(AuditLog::where('event_name', AuditEvent::NotificationSent->value)->count())
        ->toBeGreaterThan(0);

    $log = AuditLog::where('event_name', AuditEvent::NotificationSent->value)->latest('id')->first();
    expect($log->event_data)->toMatchArray([
        'notification' => CertificateReadyNotification::class,
        'channel' => 'database',
    ]);
});

it('skips auditing when notification_audit flag is off', function () {
    Config::set('features.notification_audit.enabled', false);

    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $user->notifyNow(new CertificateReadyNotification($seminar, '/go'));

    expect(AuditLog::where('event_name', AuditEvent::NotificationSent->value)->count())->toBe(0);
});
