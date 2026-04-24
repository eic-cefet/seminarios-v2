<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('enables 2FA and returns secret + QR + recovery codes', function () {
    $response = $this->postJson('/api/profile/two-factor/enable');

    $response->assertSuccessful()
        ->assertJsonStructure(['secret', 'qr_code_svg', 'recovery_codes']);

    expect($this->user->fresh()->two_factor_secret)->not->toBeNull();
    expect($this->user->fresh()->two_factor_confirmed_at)->toBeNull();
    expect(AuditLog::where('event_name', AuditEvent::UserMfaEnabled->value)->exists())->toBeTrue();
});

it('confirms 2FA when code is valid', function () {
    $this->postJson('/api/profile/two-factor/enable');
    $this->user->refresh();

    $google2fa = app(Google2FA::class);
    $code = $google2fa->getCurrentOtp(decrypt($this->user->two_factor_secret));

    $response = $this->postJson('/api/profile/two-factor/confirm', ['code' => $code]);

    $response->assertSuccessful();
    expect($this->user->fresh()->two_factor_confirmed_at)->not->toBeNull();
    expect(AuditLog::where('event_name', AuditEvent::UserMfaConfirmed->value)->exists())->toBeTrue();
});

it('rejects invalid confirmation code', function () {
    $this->postJson('/api/profile/two-factor/enable');

    $this->postJson('/api/profile/two-factor/confirm', ['code' => '000000'])
        ->assertUnprocessable();
});

it('rejects confirmation when 2FA was never initialised', function () {
    $this->postJson('/api/profile/two-factor/confirm', ['code' => '123456'])
        ->assertUnprocessable();
});

it('regenerates recovery codes', function () {
    $this->postJson('/api/profile/two-factor/enable');
    $old = decrypt($this->user->fresh()->two_factor_recovery_codes);

    $this->postJson('/api/profile/two-factor/recovery-codes')->assertSuccessful();

    $new = decrypt($this->user->fresh()->two_factor_recovery_codes);
    expect($new)->not->toBe($old);
    expect(AuditLog::where('event_name', AuditEvent::UserMfaRecoveryCodesRegenerated->value)->exists())->toBeTrue();
});

it('disables 2FA for any user who enabled it', function () {
    $this->postJson('/api/profile/two-factor/enable');

    $this->deleteJson('/api/profile/two-factor')->assertSuccessful();

    expect($this->user->fresh()->two_factor_secret)->toBeNull();
    expect(AuditLog::where('event_name', AuditEvent::UserMfaDisabled->value)->exists())->toBeTrue();
});

it('lets a regular student enable, confirm and use 2FA end-to-end', function () {
    $this->postJson('/api/profile/two-factor/enable')->assertSuccessful();
    $secret = decrypt($this->user->fresh()->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->postJson('/api/profile/two-factor/confirm', ['code' => $code])->assertSuccessful();

    expect($this->user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('also lets admins disable their own 2FA (opt-in feature)', function () {
    $this->user->assignRole(\App\Enums\Role::Admin);
    $this->postJson('/api/profile/two-factor/enable');

    $this->deleteJson('/api/profile/two-factor')->assertSuccessful();

    expect($this->user->fresh()->two_factor_secret)->toBeNull();
});
