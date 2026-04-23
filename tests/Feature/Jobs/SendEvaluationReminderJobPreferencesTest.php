<?php

use App\Jobs\SendEvaluationReminderJob;
use App\Mail\EvaluationReminder;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('does not send evaluation reminder when user opted out', function () {
    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['opted_in' => false, 'evaluation_prompt' => false],
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
    expect($registration->fresh()->evaluation_sent_at)->toBeNull();
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
