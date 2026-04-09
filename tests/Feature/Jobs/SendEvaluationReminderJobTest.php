<?php

use App\Jobs\SendEvaluationReminderJob;
use App\Mail\EvaluationReminder;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

describe('SendEvaluationReminderJob', function () {
    it('sends evaluation reminder email', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $job = new SendEvaluationReminderJob($user, collect([$registration->id]));
        $job->handle();

        Mail::assertQueued(EvaluationReminder::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        expect($registration->fresh()->evaluation_sent_at)->not->toBeNull();
    });

    it('does not send email when no registrations found', function () {
        Mail::fake();

        $user = User::factory()->create();

        $job = new SendEvaluationReminderJob($user, collect([99999]));
        $job->handle();

        Mail::assertNothingSent();
    });

    it('does not send email when registrations have no seminars', function () {
        Mail::fake();

        $user = User::factory()->create();
        // Registration with deleted seminar
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);
        $registration->seminar->delete();

        $job = new SendEvaluationReminderJob($user, collect([$registration->id]));
        $job->handle();

        Mail::assertNothingSent();
    });

    it('sends reminder for multiple registrations', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar1 = Seminar::factory()->create();
        $seminar2 = Seminar::factory()->create();

        $registration1 = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar1->id,
            'present' => true,
        ]);
        $registration2 = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar2->id,
            'present' => true,
        ]);

        $job = new SendEvaluationReminderJob($user, collect([$registration1->id, $registration2->id]));
        $job->handle();

        Mail::assertQueued(EvaluationReminder::class, function ($mail) {
            return $mail->seminars->count() === 2;
        });

        expect($registration1->fresh()->evaluation_sent_at)->not->toBeNull();
        expect($registration2->fresh()->evaluation_sent_at)->not->toBeNull();
    });
});
