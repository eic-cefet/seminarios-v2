<?php

use App\Jobs\ProcessSeminarRescheduleJob;
use App\Mail\SeminarRescheduled;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('only queues reschedule emails to users who want them', function () {
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(5)]);
    $old = now()->addDays(10);

    $optedOut = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $optedOut->id],
        ['opted_in' => false, 'seminar_rescheduled' => false],
    );

    $optedIn = User::factory()->create();
    $optedIn->alertPreference()?->delete();

    foreach ([$optedOut, $optedIn] as $u) {
        Registration::factory()->create([
            'user_id' => $u->id,
            'seminar_id' => $seminar->id,
        ]);
    }

    (new ProcessSeminarRescheduleJob($seminar, $old))->handle();

    Mail::assertQueued(SeminarRescheduled::class, 1);
    Mail::assertQueued(
        SeminarRescheduled::class,
        fn (SeminarRescheduled $m) => $m->hasTo($optedIn->email),
    );
});
