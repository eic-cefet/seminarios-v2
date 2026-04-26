<?php

use App\Jobs\BroadcastWorkshopLaunchJob;
use App\Models\Workshop;
use Illuminate\Support\Facades\Queue;

it('locks the announcement and dispatches the broadcast', function () {
    Queue::fake();
    actingAsAdmin();
    $workshop = Workshop::factory()->create(['announcement_sent_at' => null]);

    $response = $this->postJson("/api/admin/workshops/{$workshop->id}/announce");

    $response->assertSuccessful()
        ->assertJsonPath('data.announcement_sent_at', fn ($v) => $v !== null);

    expect($workshop->fresh()->announcement_sent_at)->not->toBeNull();
    Queue::assertPushed(BroadcastWorkshopLaunchJob::class, fn ($job) => $job->workshopId === $workshop->id);
});

it('refuses a second announcement for the same workshop with 409', function () {
    Queue::fake();
    actingAsAdmin();
    $workshop = Workshop::factory()->create(['announcement_sent_at' => now()]);

    $this->postJson("/api/admin/workshops/{$workshop->id}/announce")
        ->assertStatus(409);

    Queue::assertNothingPushed();
});

it('forbids non-admin users', function () {
    actingAsUser();
    $workshop = Workshop::factory()->create();

    $this->postJson("/api/admin/workshops/{$workshop->id}/announce")
        ->assertForbidden();
});

it('returns 404 for a missing workshop', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/workshops/99999/announce')
        ->assertNotFound();
});
