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
