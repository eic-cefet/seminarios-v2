<?php

use App\Models\MfaTrustedDevice;
use App\Models\User;
use App\Services\TwoFactorDeviceService;

beforeEach(fn () => $this->service = app(TwoFactorDeviceService::class));

it('issues a token and persists the hash', function () {
    $user = User::factory()->create();

    $token = $this->service->issue($user, 'My Laptop', '127.0.0.1');

    expect($token)->toBeString()->toHaveLength(64);
    expect(MfaTrustedDevice::where('user_id', $user->id)->count())->toBe(1);
    $row = MfaTrustedDevice::first();
    expect($row->token_hash)->toBe(hash('sha256', $token));
    expect($row->label)->toBe('My Laptop');
});

it('verifies a valid non-expired token', function () {
    $user = User::factory()->create();
    $token = $this->service->issue($user, 'X', '1.2.3.4');

    expect($this->service->isTrusted($user, $token))->toBeTrue();
    expect(MfaTrustedDevice::first()->last_used_at)->not->toBeNull();
});

it('rejects expired tokens', function () {
    $user = User::factory()->create();
    $token = $this->service->issue($user, 'X', '1.2.3.4');
    MfaTrustedDevice::first()->update(['expires_at' => now()->subMinute()]);

    expect($this->service->isTrusted($user, $token))->toBeFalse();
});

it('rejects unknown tokens', function () {
    $user = User::factory()->create();
    expect($this->service->isTrusted($user, 'garbage'))->toBeFalse();
});

it('rejects null tokens', function () {
    $user = User::factory()->create();
    expect($this->service->isTrusted($user, null))->toBeFalse();
});

it('revokes a single device', function () {
    $user = User::factory()->create();
    $device = MfaTrustedDevice::factory()->for($user)->create();

    $this->service->revoke($user, $device->id);

    expect(MfaTrustedDevice::find($device->id))->toBeNull();
});

it('revokes all devices for a user', function () {
    $user = User::factory()->create();
    MfaTrustedDevice::factory()->for($user)->count(3)->create();

    $this->service->revokeAll($user);

    expect($user->trustedDevices()->count())->toBe(0);
});
