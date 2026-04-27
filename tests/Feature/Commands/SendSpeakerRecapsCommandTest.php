<?php

use App\Jobs\SendSpeakerRecapJob;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dispatches a recap job per speaker for seminars past D+2 with no recap yet', function () {
    Queue::fake();
    $this->travelTo('2026-05-10 12:00:00');
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'scheduled_at' => '2026-05-08 11:00:00',
        'active' => true,
    ]);
    $seminar->speakers()->attach($speaker->id);

    $this->artisan('reminders:speaker-recaps')->assertOk();

    Queue::assertPushed(SendSpeakerRecapJob::class, fn ($job) => $job->speaker->is($speaker) && $job->seminarId === $seminar->id
    );
});

it('skips speakers already stamped with recap_sent_at', function () {
    Queue::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);
    $seminar->speakers()->attach($speaker->id, ['recap_sent_at' => now()]);

    $this->artisan('reminders:speaker-recaps')->assertOk();

    Queue::assertNothingPushed();
});

it('skips seminars not yet past D+2', function () {
    Queue::fake();
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDay()]);
    $seminar->speakers()->attach($speaker->id);

    $this->artisan('reminders:speaker-recaps')->assertOk();

    Queue::assertNothingPushed();
});

it('skips seminars scheduled on or before the SCHEDULED_AFTER cutoff', function () {
    Queue::fake();
    $this->travelTo('2026-05-10 12:00:00');
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'scheduled_at' => '2026-04-26 12:00:00',
        'active' => true,
    ]);
    $seminar->speakers()->attach($speaker->id);

    $this->artisan('reminders:speaker-recaps')->assertOk();

    Queue::assertNothingPushed();
});
