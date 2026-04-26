<?php

use App\Jobs\BroadcastWorkshopLaunchJob;
use App\Jobs\SendWorkshopAvailableJob;
use App\Models\AlertPreference;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\Queue;

it('dispatches a per-user job for every user without an alert preference (default-on)', function () {
    Queue::fake();
    $users = User::factory()->count(3)->create();
    $workshop = Workshop::factory()->create();

    (new BroadcastWorkshopLaunchJob($workshop->id))->handle();

    Queue::assertPushed(SendWorkshopAvailableJob::class, 3);
    foreach ($users as $u) {
        Queue::assertPushed(SendWorkshopAvailableJob::class, fn ($job) => $job->user->is($u) && $job->workshopId === $workshop->id);
    }
});

it('dispatches for users who have opted in', function () {
    Queue::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create(['workshop_announcements' => true]);
    $workshop = Workshop::factory()->create();

    (new BroadcastWorkshopLaunchJob($workshop->id))->handle();

    Queue::assertPushed(SendWorkshopAvailableJob::class, 1);
});

it('skips users who have opted out at fan-out time', function () {
    Queue::fake();
    $optedIn = User::factory()->create();
    $optedOut = User::factory()->create();
    AlertPreference::factory()->for($optedIn)->create(['workshop_announcements' => true]);
    AlertPreference::factory()->for($optedOut)->create(['workshop_announcements' => false]);
    $workshop = Workshop::factory()->create();

    (new BroadcastWorkshopLaunchJob($workshop->id))->handle();

    Queue::assertPushed(SendWorkshopAvailableJob::class, 1);
    Queue::assertPushed(SendWorkshopAvailableJob::class, fn ($job) => $job->user->is($optedIn));
});
