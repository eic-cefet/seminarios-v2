<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\MfaTrustedDevice;
use App\Models\User;
use App\Services\TwoFactorDeviceService;
use PragmaRX\Google2FA\Google2FA;

it('issues a trusted-device cookie when remember_device is true', function () {
    $user = userWithConfirmed2fa();
    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');
    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

    $response = $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => $code,
        'remember_device' => true,
    ]);

    $response->assertSuccessful()
        ->assertCookie(TwoFactorDeviceService::COOKIE_NAME);
    expect($user->fresh()->trustedDevices()->count())->toBe(1);
    expect(AuditLog::where('event_name', AuditEvent::UserMfaDeviceRemembered->value)->exists())->toBeTrue();
});

it('skips the challenge when a trusted-device cookie is presented', function () {
    $user = userWithConfirmed2fa();
    $token = app(TwoFactorDeviceService::class)->issue($user, 'Prev', '127.0.0.1');

    $this->disableCookieEncryption();
    $response = $this->call(
        'POST',
        '/api/auth/login',
        [],
        [TwoFactorDeviceService::COOKIE_NAME => $token],
        [],
        ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
        json_encode(['email' => $user->email, 'password' => 'secret123']),
    );

    $response->assertSuccessful()->assertJsonPath('user.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it('lists the current user trusted devices', function () {
    $user = User::factory()->create();
    MfaTrustedDevice::factory()->for($user)->count(2)->create();
    $this->actingAs($user);

    $this->getJson('/api/profile/two-factor/devices')
        ->assertSuccessful()
        ->assertJsonCount(2, 'devices');
});

it('revokes a trusted device', function () {
    $user = User::factory()->create();
    $device = MfaTrustedDevice::factory()->for($user)->create();
    $this->actingAs($user);

    $this->deleteJson("/api/profile/two-factor/devices/{$device->id}")->assertSuccessful();

    expect(MfaTrustedDevice::find($device->id))->toBeNull();
    expect(AuditLog::where('event_name', AuditEvent::UserMfaDeviceRevoked->value)->exists())->toBeTrue();
});

it('cannot revoke another users device', function () {
    $mine = User::factory()->create();
    $theirs = User::factory()->create();
    $device = MfaTrustedDevice::factory()->for($theirs)->create();
    $this->actingAs($mine);

    $this->deleteJson("/api/profile/two-factor/devices/{$device->id}")->assertNotFound();
});
