<?php

use App\Jobs\SendSeminarReminderJob;
use App\Mail\SeminarReminder;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('does not send seminar reminder when user opted out of 24h reminders', function () {
    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['opted_in' => false, 'seminar_reminder_24h' => false],
    );

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'reminder_sent' => false,
    ]);

    (new SendSeminarReminderJob($user->fresh(), collect([$registration->id])))->handle();

    Mail::assertNothingQueued();
    Mail::assertNothingSent();
    expect($registration->fresh()->reminder_sent)->toBeFalse();
});

it('sends seminar reminder when user has no preferences row (opt-out default)', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();

    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'reminder_sent' => false,
    ]);

    (new SendSeminarReminderJob($user->fresh(), collect([$registration->id])))->handle();

    Mail::assertQueued(SeminarReminder::class);
    expect($registration->fresh()->reminder_sent)->toBeTrue();
});
