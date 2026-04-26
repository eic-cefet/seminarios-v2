<?php

use App\Enums\AuditEvent;
use App\Enums\CommunicationCategory;
use App\Jobs\SendSeminarReminderJob;
use App\Models\AlertPreference;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('dispatches a 24h reminder job by default for seminars happening tomorrow', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();
    $seminar = Seminar::factory()->create([
        'scheduled_at' => now()->addDay()->setTime(10, 0),
        'active' => true,
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'reminder_sent' => false,
    ]);

    $this->artisan('reminders:seminars')->assertOk();

    Queue::assertPushed(SendSeminarReminderJob::class, fn ($job) => $job->user->is($user)
        && $job->category === CommunicationCategory::SeminarReminder24h
        && $job->reminderColumn === 'reminder_sent'
    );

    expect(AuditLog::forEvent(AuditEvent::SeminarRemindersSent)->exists())->toBeTrue();
});

it('dispatches a 7-day reminder job for users with seminar_reminder_7d=true', function () {
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create(['seminar_reminder_7d' => true]);
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)->setTime(10, 0), 'active' => true]);
    Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id, 'reminder_7d_sent' => false]);

    $this->artisan('reminders:seminars', ['--days' => 7])->assertOk();

    Queue::assertPushed(SendSeminarReminderJob::class, fn ($job) => $job->user->is($user)
        && $job->category === CommunicationCategory::SeminarReminder7d
        && $job->reminderColumn === 'reminder_7d_sent'
    );

    expect(AuditLog::forEvent(AuditEvent::Seminar7dRemindersSent)->exists())->toBeTrue();
});

it('skips users with seminar_reminder_7d=false', function () {
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create(['seminar_reminder_7d' => false]);
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7), 'active' => true]);
    Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id, 'reminder_7d_sent' => false]);

    $this->artisan('reminders:seminars', ['--days' => 7])->assertOk();

    Queue::assertNotPushed(SendSeminarReminderJob::class);
});

it('rejects --days values other than 1 or 7', function () {
    $this->artisan('reminders:seminars', ['--days' => 3])
        ->expectsOutput('--days must be 1 or 7')
        ->assertFailed();
});
