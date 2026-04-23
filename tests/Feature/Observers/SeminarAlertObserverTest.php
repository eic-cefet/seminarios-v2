<?php

use App\Jobs\DispatchSeminarAlertsJob;
use App\Models\AlertPreference;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function optInAUser(): User
{
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();

    return $user;
}

it('dispatches job when a seminar is created active and users are opted-in', function () {
    optInAUser();
    Bus::fake();

    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::assertDispatched(DispatchSeminarAlertsJob::class, fn ($j) => $j->seminar->is($seminar));
});

it('does not dispatch when a seminar is created inactive', function () {
    optInAUser();
    Bus::fake();

    Seminar::factory()->create(['active' => false]);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});

it('dispatches job when a seminar transitions from inactive to active', function () {
    optInAUser();
    $seminar = Seminar::factory()->create(['active' => false]);

    Bus::fake();
    $seminar->update(['active' => true]);

    Bus::assertDispatched(DispatchSeminarAlertsJob::class, fn ($j) => $j->seminar->is($seminar));
});

it('does not dispatch when an already-active seminar is updated', function () {
    optInAUser();
    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::fake();
    $seminar->update(['name' => 'Novo nome']);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});

it('does not dispatch when seminar transitions from active to inactive', function () {
    optInAUser();
    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::fake();
    $seminar->update(['active' => false]);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});

it('does not dispatch when seminar is active but no users are opted-in', function () {
    Bus::fake();

    Seminar::factory()->create(['active' => true]);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});
