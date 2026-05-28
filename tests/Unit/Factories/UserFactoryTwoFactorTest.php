<?php

use App\Models\User;

it('seeds confirmed 2FA columns on the user', function () {
    $user = User::factory()->withTwoFactor()->create();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_recovery_codes)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
});
