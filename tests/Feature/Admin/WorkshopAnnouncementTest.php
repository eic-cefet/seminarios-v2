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

it('refuses workshops created before the announce-feature cutoff with 422', function () {
    Queue::fake();
    actingAsAdmin();
    $workshop = Workshop::factory()->create([
        'announcement_sent_at' => null,
        'created_at' => '2026-04-25 23:59:59',
    ]);

    $this->postJson("/api/admin/workshops/{$workshop->id}/announce")
        ->assertStatus(422)
        ->assertJsonPath('error', 'workshop_too_old_to_announce');

    expect($workshop->fresh()->announcement_sent_at)->toBeNull();
    Queue::assertNothingPushed();
});

it('allows workshops created exactly on the cutoff date', function () {
    Queue::fake();
    actingAsAdmin();
    $workshop = Workshop::factory()->create([
        'announcement_sent_at' => null,
        'created_at' => '2026-04-26 00:00:00',
    ]);

    $this->postJson("/api/admin/workshops/{$workshop->id}/announce")->assertSuccessful();

    Queue::assertPushed(BroadcastWorkshopLaunchJob::class);
});
