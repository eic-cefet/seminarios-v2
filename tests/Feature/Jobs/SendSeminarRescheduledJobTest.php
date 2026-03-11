<?php

use App\Jobs\SendSeminarRescheduledJob;
use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

describe('SendSeminarRescheduledJob', function () {
    it('sends rescheduled email to user', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $job = new SendSeminarRescheduledJob($user, $seminar, $oldScheduledAt);
        $job->handle();

        Mail::assertSent(SeminarRescheduled::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    });

    it('includes seminar and old date in the mailable', function () {
        Mail::fake();

        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $job = new SendSeminarRescheduledJob($user, $seminar, $oldScheduledAt);
        $job->handle();

        Mail::assertSent(SeminarRescheduled::class, function ($mail) use ($seminar, $oldScheduledAt) {
            return $mail->seminar->id === $seminar->id
                && $mail->oldScheduledAt->equalTo($oldScheduledAt);
        });
    });

    it('serializes oldScheduledAt as ISO string for queue safety', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $job = new SendSeminarRescheduledJob($user, $seminar, $oldScheduledAt);

        expect($job->oldScheduledAt)->toBeString();
        expect($job->oldScheduledAt)->toBe($oldScheduledAt->format(\DateTimeInterface::ATOM));
    });

    it('logs error on failure', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $job = new SendSeminarRescheduledJob($user, $seminar, $oldScheduledAt);

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($user, $seminar) {
                return $message === 'SendSeminarRescheduledJob failed'
                    && $context['user_id'] === $user->id
                    && $context['seminar_id'] === $seminar->id;
            });

        $job->failed(new \RuntimeException('SMTP connection failed'));
    });
});
