<?php

use App\Jobs\DispatchSeminarAlertsJob;
use App\Models\Seminar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches job when a seminar is created active', function () {
    Bus::fake();

    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::assertDispatched(DispatchSeminarAlertsJob::class, fn ($j) => $j->seminar->is($seminar));
});

it('does not dispatch when a seminar is created inactive', function () {
    Bus::fake();

    Seminar::factory()->create(['active' => false]);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});

it('dispatches job when a seminar transitions from inactive to active', function () {
    $seminar = Seminar::factory()->create(['active' => false]);

    Bus::fake();
    $seminar->update(['active' => true]);

    Bus::assertDispatched(DispatchSeminarAlertsJob::class, fn ($j) => $j->seminar->is($seminar));
});

it('does not dispatch when an already-active seminar is updated', function () {
    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::fake();
    $seminar->update(['name' => 'Novo nome']);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});

it('does not dispatch when seminar transitions from active to inactive', function () {
    $seminar = Seminar::factory()->create(['active' => true]);

    Bus::fake();
    $seminar->update(['active' => false]);

    Bus::assertNotDispatched(DispatchSeminarAlertsJob::class);
});
