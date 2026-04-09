<?php

use App\Jobs\SendSeminarReminderJob;
use App\Mail\SeminarReminder;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

describe('SendSeminarReminderJob', function () {
    it('sends seminar reminder email', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $job = new SendSeminarReminderJob($user, collect([$registration->id]));
        $job->handle();

        Mail::assertQueued(SeminarReminder::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        expect($registration->fresh()->reminder_sent)->toBeTrue();
    });

    it('does not send email when no registrations found', function () {
        Mail::fake();

        $user = User::factory()->create();

        $job = new SendSeminarReminderJob($user, collect([99999]));
        $job->handle();

        Mail::assertNothingSent();
    });

    it('does not send email when registrations have no seminars', function () {
        Mail::fake();

        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);
        $registration->seminar->delete();

        $job = new SendSeminarReminderJob($user, collect([$registration->id]));
        $job->handle();

        Mail::assertNothingSent();
    });

    it('sends reminder for multiple registrations', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar1 = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);
        $seminar2 = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $registration1 = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar1->id,
            'reminder_sent' => false,
        ]);
        $registration2 = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar2->id,
            'reminder_sent' => false,
        ]);

        $job = new SendSeminarReminderJob($user, collect([$registration1->id, $registration2->id]));
        $job->handle();

        Mail::assertQueued(SeminarReminder::class, function ($mail) {
            return $mail->seminars->count() === 2;
        });

        expect($registration1->fresh()->reminder_sent)->toBeTrue();
        expect($registration2->fresh()->reminder_sent)->toBeTrue();
    });
});
