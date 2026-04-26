<?php

use App\Jobs\BroadcastWorkshopLaunchJob;
use App\Jobs\SendWorkshopAvailableJob;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\Queue;

it('dispatches a per-user job for every user', function () {
    Queue::fake();
    $users = User::factory()->count(3)->create();
    $workshop = Workshop::factory()->create();

    (new BroadcastWorkshopLaunchJob($workshop->id))->handle();

    Queue::assertPushed(SendWorkshopAvailableJob::class, 3);
    foreach ($users as $u) {
        Queue::assertPushed(SendWorkshopAvailableJob::class, fn ($job) => $job->user->is($u) && $job->workshopId === $workshop->id);
    }
});
