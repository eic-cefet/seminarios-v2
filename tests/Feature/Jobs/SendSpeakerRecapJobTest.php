<?php

use App\Jobs\SendSpeakerRecapJob;
use App\Mail\SpeakerSeminarRecap;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('queues SpeakerSeminarRecap with present-attendee count and stamps recap_sent_at', function () {
    Mail::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->speakers()->attach($speaker->id);
    Registration::factory()->count(3)->create(['seminar_id' => $seminar->id, 'present' => true]);
    Registration::factory()->count(2)->create(['seminar_id' => $seminar->id, 'present' => false]);

    (new SendSpeakerRecapJob($speaker, $seminar->id))->handle();

    Mail::assertQueued(SpeakerSeminarRecap::class, fn ($m) => $m->speaker->is($speaker) && $m->seminar->is($seminar) && $m->attendeesPresent === 3);
    expect(DB::table('seminar_speaker')
        ->where(['seminar_id' => $seminar->id, 'user_id' => $speaker->id])
        ->value('recap_sent_at'))->not->toBeNull();
});

it('skips when already stamped', function () {
    Mail::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->speakers()->attach($speaker->id, ['recap_sent_at' => now()]);

    (new SendSpeakerRecapJob($speaker, $seminar->id))->handle();

    Mail::assertNothingQueued();
});

it('skips when no pivot row exists', function () {
    Mail::fake();
    $speaker = User::factory()->create();

    (new SendSpeakerRecapJob($speaker, 99999))->handle();

    Mail::assertNothingQueued();
});

it('skips when no attendees were present, but stamps recap_sent_at to avoid retry', function () {
    Mail::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->speakers()->attach($speaker->id);

    (new SendSpeakerRecapJob($speaker, $seminar->id))->handle();

    Mail::assertNothingQueued();
    expect(DB::table('seminar_speaker')
        ->where(['seminar_id' => $seminar->id, 'user_id' => $speaker->id])
        ->value('recap_sent_at'))->not->toBeNull();
});
