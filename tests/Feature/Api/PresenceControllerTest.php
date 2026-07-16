<?php

use App\Models\PresenceLink;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\BadgesUnlockedNotification;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;

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
        ->assertJsonPath('error', 'invalid_presence_link');
});

it('returns 400 for inactive presence link', function () {
    $presenceLink = PresenceLink::factory()->inactive()->create();

    $response = $this->getJson("/api/presence/{$presenceLink->uuid}");

    $response->assertStatus(400)
        ->assertJsonPath('error', 'invalid_presence_link');
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

it('returns newly earned progress on the first scan and no delta on a repeated scan', function () {
    Notification::fake();

    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    $firstResponse = $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register");

    $firstResponse->assertSuccessful()
        ->assertJsonPath('gamification.xp_earned', 125)
        ->assertJsonPath('gamification.total_xp', 125)
        ->assertJsonPath('gamification.new_badges.0.key', 'first_presence')
        ->assertJsonPath('gamification.new_badges.0.description', 'Participe de uma apresentação.')
        ->assertJsonMissingPath('gamification.new_badges.0.metric')
        ->assertJsonMissingPath('gamification.new_badges.0.threshold');

    expect($firstResponse->json('gamification.level.level'))->toBeInt();
    Notification::assertSentToTimes($user, BadgesUnlockedNotification::class, 1);

    $repeatResponse = $this->postJson("/api/presence/{$presenceLink->uuid}/register");

    $repeatResponse->assertSuccessful()
        ->assertJsonPath('gamification.xp_earned', 0)
        ->assertJsonPath('gamification.total_xp', 125)
        ->assertJsonCount(0, 'gamification.new_badges');
    Notification::assertSentToTimes($user, BadgesUnlockedNotification::class, 1);
});

it('keeps a registered presence when gamification reconciliation fails', function () {
    Exceptions::fake();

    $exception = new RuntimeException('gamification unavailable');
    $gamification = Mockery::mock(GamificationService::class);
    $gamification->shouldReceive('sync')->once()->andThrow($exception);
    app()->instance(GamificationService::class, $gamification);

    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register")
        ->assertSuccessful()
        ->assertJsonPath('gamification', null);

    $this->assertDatabaseHas('registrations', [
        'user_id' => $user->id,
        'seminar_id' => $presenceLink->seminar_id,
        'present' => true,
    ]);
    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
});

it('reports notification delivery errors without failing presence or progress', function () {
    Exceptions::fake();

    $exception = new RuntimeException('notification channel unavailable');
    Notification::shouldReceive('send')->once()->andThrow($exception);

    $user = User::factory()->create();
    $presenceLink = PresenceLink::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/presence/{$presenceLink->uuid}/register")
        ->assertSuccessful()
        ->assertJsonPath('gamification.new_badges.0.key', 'first_presence');

    $this->assertDatabaseHas('registrations', [
        'user_id' => $user->id,
        'seminar_id' => $presenceLink->seminar_id,
        'present' => true,
    ]);
    $this->assertDatabaseHas('user_badges', [
        'user_id' => $user->id,
        'badge_key' => 'first_presence',
    ]);
    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
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
        ->assertJsonPath('error', 'invalid_presence_link');
});

it('returns QR code PNG for valid presence link', function () {
    $presenceLink = PresenceLink::factory()->create();

    $response = $this->get("/p/{$presenceLink->uuid}.png");

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png');
});

it('returns 404 for QR code PNG with non-existent link', function () {
    $response = $this->get('/p/non-existent-uuid.png');

    $response->assertNotFound();
});

it('returns 400 for QR code PNG with expired link', function () {
    $presenceLink = PresenceLink::factory()->expired()->create();

    $response = $this->get("/p/{$presenceLink->uuid}.png");

    $response->assertStatus(400);
});

it('returns 400 for QR code PNG with inactive link', function () {
    $presenceLink = PresenceLink::factory()->inactive()->create();

    $response = $this->get("/p/{$presenceLink->uuid}.png");

    $response->assertStatus(400);
});
