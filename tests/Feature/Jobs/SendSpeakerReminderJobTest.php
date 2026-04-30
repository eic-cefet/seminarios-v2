<?php

use App\Jobs\SendSpeakerReminderJob;
use App\Mail\SpeakerSeminarReminder;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('queues the speaker reminder and stamps reminder_24h_sent_at idempotently', function () {
    Mail::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->speakers()->attach($speaker->id);

    (new SendSpeakerReminderJob($speaker, $seminar->id))->handle();

    Mail::assertQueued(SpeakerSeminarReminder::class, fn ($m) => $m->speaker->is($speaker) && $m->seminar->is($seminar));
    expect(DB::table('seminar_speaker')
        ->where(['seminar_id' => $seminar->id, 'user_id' => $speaker->id])
        ->value('reminder_24h_sent_at'))->not->toBeNull();
});

it('skips when reminder_24h_sent_at is already set', function () {
    Mail::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->speakers()->attach($speaker->id, ['reminder_24h_sent_at' => now()]);

    (new SendSpeakerReminderJob($speaker, $seminar->id))->handle();

    Mail::assertNothingQueued();
});

it('skips when the seminar no longer exists', function () {
    Mail::fake();
    $speaker = User::factory()->create();

    (new SendSpeakerReminderJob($speaker, 99999))->handle();

    Mail::assertNothingQueued();
});
