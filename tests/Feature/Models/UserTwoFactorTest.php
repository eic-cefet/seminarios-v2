<?php

use App\Models\MfaTrustedDevice;
use App\Models\User;

it('user has two-factor columns hidden and castable', function () {
    $user = User::factory()->create();
    $user->two_factor_secret = encrypt('abc');
    $user->save();

    $fresh = $user->refresh();

    expect(decrypt($fresh->two_factor_secret))->toBe('abc');
    expect($fresh->toArray())->not->toHaveKey('two_factor_secret');
});

it('user has trustedDevices relationship', function () {
    $user = User::factory()->create();
    expect($user->trustedDevices()->getRelated())->toBeInstanceOf(MfaTrustedDevice::class);
});
