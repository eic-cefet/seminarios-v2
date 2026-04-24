<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Services\TwoFactorDeviceService;

it('issues a trusted-device cookie when remember_device is true', function () {
    $user = userWithConfirmed2fa();
    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');
    $code = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

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
