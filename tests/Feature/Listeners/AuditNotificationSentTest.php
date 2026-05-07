<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Config::set('features.notification_audit.enabled', true);
    // Run notifications synchronously so the listener fires within the test.
    Queue::fake();
});

it('writes exactly one AuditLog row when an in-app notification is sent', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $user->notifyNow(new CertificateReadyNotification($seminar, '/go'));

    $logs = AuditLog::forEvent(AuditEvent::NotificationSent)->get();
    expect($logs)->toHaveCount(1);
    expect($logs->first()->event_data)->toMatchArray([
        'notification' => CertificateReadyNotification::class,
        'channel' => 'database',
    ]);
    expect($logs->first()->ref_id)->toContain('notification:');
    expect($logs->first()->ref_id)->toContain(':database');
});

it('skips auditing when notification_audit flag is off', function () {
    Config::set('features.notification_audit.enabled', false);

    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $user->notifyNow(new CertificateReadyNotification($seminar, '/go'));

    expect(AuditLog::forEvent(AuditEvent::NotificationSent)->count())->toBe(0);
});

it('dedupes audits for the same logical notification via the ref_id unique index', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $notification = new CertificateReadyNotification($seminar, '/go');
    // Stamp the same notification id the framework would have stamped in
    // sendNow(); this is what makes two NotificationSent dispatches refer
    // to the same logical delivery.
    $notification->id = (string) Str::uuid();

    event(new NotificationSent($user, $notification, 'database'));
    event(new NotificationSent($user, $notification, 'database'));
    event(new NotificationSent($user, $notification, 'database'));

    expect(AuditLog::forEvent(AuditEvent::NotificationSent)->count())->toBe(1);
});

it('records separate audits for distinct notification deliveries', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $first = new CertificateReadyNotification($seminar, '/go');
    $first->id = (string) Str::uuid();

    $second = new CertificateReadyNotification($seminar, '/go');
    $second->id = (string) Str::uuid();

    event(new NotificationSent($user, $first, 'database'));
    event(new NotificationSent($user, $second, 'database'));

    expect(AuditLog::forEvent(AuditEvent::NotificationSent)->count())->toBe(2);
});
