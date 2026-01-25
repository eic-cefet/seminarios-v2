<?php

use App\Models\PresenceLink;
use App\Models\Registration;
use App\Models\User;

it('shows presence link info for valid uuid', function () {
    $presenceLink = PresenceLink::factory()->create();

    $response = $this->getJson("/api/presence/{$presenceLink->uuid}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'seminar' => ['id', 'name', 'scheduled_at'],
                'is_valid',
                'expires_at',
            ],
        ])
        ->assertJsonPath('data.is_valid', true);
});

it('returns 404 for non-existent presence link', function () {
    $response = $this->getJson('/api/presence/non-existent-uuid');

    $response->assertNotFound();
});

it('returns 400 for expired presence link', function () {
    $presenceLink = PresenceLink::factory()->expired()->create();

    $response = $this->getJson("/api/presence/{$presenceLink->uuid}");

    $response->assertStatus(400)
        ->assertJsonPath('is_valid', false)
        ->assertJsonPath('is_expired', true);
});

it('returns 400 for inactive presence link', function () {
    $presenceLink = PresenceLink::factory()->inactive()->create();

    $response = $this->getJson("/api/presence/{$presenceLink->uuid}");

    $response->assertStatus(400)
        ->assertJsonPath('is_valid', false)
        ->assertJsonPath('is_active', false);
});

it('requires authentication to register presence', function () {
    $presenceLink = PresenceLink::factory()->create();

    $response = $this->postJson("/api/presence/{$presenceLink->uuid}/register");

    $response->assertUnauthorized()
        ->assertJsonPath('requires_auth', true);
});

it('registers presence for authenticated user', function () {
    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register");

    $response->assertSuccessful()
        ->assertJsonPath('data.present', true)
        ->assertJsonPath('data.seminar.id', $presenceLink->seminar_id);

    $this->assertDatabaseHas('registrations', [
        'user_id' => $user->id,
        'seminar_id' => $presenceLink->seminar_id,
        'present' => true,
    ]);
});

it('returns success for already registered and present user (idempotent)', function () {
    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    Registration::factory()->present()->create([
        'user_id' => $user->id,
        'seminar_id' => $presenceLink->seminar_id,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register");

    $response->assertSuccessful()
        ->assertJsonPath('data.present', true);
});

it('marks existing registration as present', function () {
    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $presenceLink->seminar_id,
        'present' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register");

    $response->assertSuccessful();

    expect($registration->fresh()->present)->toBeTrue();
});

it('returns 404 when registering with non-existent link', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/presence/non-existent-uuid/register');

    $response->assertNotFound();
});

it('returns 400 when registering with expired link', function () {
    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->expired()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register");

    $response->assertStatus(400)
        ->assertJsonPath('is_valid', false);
});
