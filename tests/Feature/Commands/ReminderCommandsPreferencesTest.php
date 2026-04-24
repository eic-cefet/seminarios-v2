<?php

use App\Jobs\SendEvaluationReminderJob;
use App\Jobs\SendSeminarReminderJob;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('reminders:seminars skips users opted out of 24h reminder', function () {
    $optedOut = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $optedOut->id],
        ['new_seminar_alert' => false, 'seminar_reminder_24h' => false],
    );

    $optedIn = User::factory()->create();
    $optedIn->alertPreference()?->delete();

    $seminar = Seminar::factory()->create([
        'scheduled_at' => now()->addDay()->setTime(10, 0),
        'active' => true,
    ]);

    foreach ([$optedOut, $optedIn] as $u) {
        Registration::factory()->create([
            'user_id' => $u->id,
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);
    }

    $this->artisan('reminders:seminars')->assertSuccessful();

    Queue::assertPushed(SendSeminarReminderJob::class, 1);
    Queue::assertPushed(
        SendSeminarReminderJob::class,
        fn (SendSeminarReminderJob $job) => $job->user->is($optedIn),
    );
});

it('reminders:evaluations skips users opted out of evaluation prompt', function () {
    $optedOut = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $optedOut->id],
        ['new_seminar_alert' => false, 'evaluation_prompt' => false],
    );

    $optedIn = User::factory()->create();
    $optedIn->alertPreference()?->delete();

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);

    foreach ([$optedOut, $optedIn] as $u) {
        Registration::factory()->create([
            'user_id' => $u->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);
    }

    $this->artisan('reminders:evaluations')->assertSuccessful();

    Queue::assertPushed(SendEvaluationReminderJob::class, 1);
    Queue::assertPushed(
        SendEvaluationReminderJob::class,
        fn (SendEvaluationReminderJob $job) => $job->user->is($optedIn),
    );
});
