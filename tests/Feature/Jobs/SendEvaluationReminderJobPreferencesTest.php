<?php

use App\Jobs\SendEvaluationReminderJob;
use App\Mail\EvaluationReminder;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\EvaluationDueNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
});

it('does not send evaluation reminder when user opted out', function () {
    Notification::fake();

    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['new_seminar_alert' => false, 'evaluation_prompt' => false],
    );

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
        'evaluation_sent_at' => null,
    ]);

    (new SendEvaluationReminderJob($user->fresh(), collect([$registration->id])))->handle();

    Mail::assertNothingQueued();
    Mail::assertNothingSent();
    // evaluation_sent_at is now stamped unconditionally so reminders don't re-enqueue,
    // even when the user has opted out of the email channel.
    expect($registration->fresh()->evaluation_sent_at)->not->toBeNull();
});

it('still dispatches EvaluationDueNotification when user opted out of evaluation email', function () {
    Notification::fake();

    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['new_seminar_alert' => false, 'evaluation_prompt' => false],
    );

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
        'evaluation_sent_at' => null,
    ]);

    (new SendEvaluationReminderJob($user->fresh(), collect([$registration->id])))->handle();

    Notification::assertSentTo($user->fresh(), EvaluationDueNotification::class);
    expect($registration->fresh()->evaluation_sent_at)->not->toBeNull();
});

it('sends evaluation reminder when user has no preferences row', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
        'evaluation_sent_at' => null,
    ]);

    (new SendEvaluationReminderJob($user->fresh(), collect([$registration->id])))->handle();

    Mail::assertQueued(EvaluationReminder::class);
    expect($registration->fresh()->evaluation_sent_at)->not->toBeNull();
});
