<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

function userWithConfirmed2fa(): User
{
    $user = User::factory()->create(['password' => Hash::make('secret123')]);
    $google = app(Google2FA::class);
    $user->forceFill([
        'two_factor_secret' => encrypt($google->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

it('returns challenge instead of user when 2FA is confirmed', function () {
    $user = userWithConfirmed2fa();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)->assertJsonStructure(['two_factor' => ['challenge_token']]);
    $this->assertGuest();
});

it('logs in normally when user has no confirmed 2FA', function () {
    $user = User::factory()->create(['password' => Hash::make('secret123')]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertSuccessful()->assertJsonPath('user.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it('completes login with a valid TOTP code', function () {
    $user = userWithConfirmed2fa();

    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');

    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

    $response = $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => $code,
    ]);

    $response->assertSuccessful()->assertJsonPath('user.email', $user->email);
    $this->assertAuthenticatedAs($user);
    expect(\App\Models\AuditLog::where('event_name', \App\Enums\AuditEvent::UserMfaUsed->value)->exists())->toBeTrue();
});

it('rejects invalid TOTP code', function () {
    $user = userWithConfirmed2fa();
    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');

    $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => '000000',
    ])->assertUnprocessable();

    $this->assertGuest();
    expect(\App\Models\AuditLog::where('event_name', \App\Enums\AuditEvent::UserMfaChallengeFailed->value)->exists())->toBeTrue();
});

it('accepts a recovery code once and removes it', function () {
    $user = userWithConfirmed2fa();
    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');

    $response = $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'recovery_code' => 'code-one',
    ]);

    $response->assertSuccessful();
    $this->assertAuthenticatedAs($user);

    $remaining = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($remaining)->not->toContain('code-one')->toContain('code-two');
    expect(\App\Models\AuditLog::where('event_name', \App\Enums\AuditEvent::UserMfaRecoveryCodeUsed->value)->exists())->toBeTrue();
});

it('rejects unknown recovery code', function () {
    $user = userWithConfirmed2fa();
    $challenge = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
        ->json('two_factor.challenge_token');

    $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'recovery_code' => 'nope',
    ])->assertUnprocessable();
});

it('rejects expired or unknown challenge tokens', function () {
    $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => 'garbage',
        'code' => '123456',
    ])->assertUnprocessable();
});
