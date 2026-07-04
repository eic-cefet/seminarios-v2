<?php

use App\Jobs\SendWorkshopAvailableJob;
use App\Mail\WorkshopAvailable;
use App\Models\AlertPreference;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\Mail;

it('queues the workshop available mail when the user opted in', function () {
    Mail::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create(['workshop_announcements' => true]);
    $workshop = Workshop::factory()->create();

    (new SendWorkshopAvailableJob($user, $workshop->id))->handle();

    Mail::assertQueued(WorkshopAvailable::class, fn ($m) => $m->user->is($user) && $m->workshop->is($workshop));
});

it('skips when the user opted out', function () {
    Mail::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create(['workshop_announcements' => false]);
    $workshop = Workshop::factory()->create();

    (new SendWorkshopAvailableJob($user, $workshop->id))->handle();

    Mail::assertNothingQueued();
});

it('queues the mail for users without an AlertPreference row (default-on)', function () {
    Mail::fake();
    $user = User::factory()->create();
    expect($user->alertPreference)->toBeNull();
    $workshop = Workshop::factory()->create();

    (new SendWorkshopAvailableJob($user, $workshop->id))->handle();

    Mail::assertQueued(WorkshopAvailable::class);
});

it('skips when the workshop no longer exists', function () {
    Mail::fake();
    $user = User::factory()->create();

    (new SendWorkshopAvailableJob($user, 99999))->handle();

    Mail::assertNothingQueued();
});
