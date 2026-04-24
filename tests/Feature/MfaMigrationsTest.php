<?php

use Illuminate\Support\Facades\Schema;

it('adds two-factor columns to users', function () {
    expect(Schema::hasColumns('users', [
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ]))->toBeTrue();
});

it('creates mfa_trusted_devices table', function () {
    expect(Schema::hasTable('mfa_trusted_devices'))->toBeTrue();
    expect(Schema::hasColumns('mfa_trusted_devices', [
        'id', 'user_id', 'token_hash', 'label', 'last_used_at', 'expires_at', 'created_at',
    ]))->toBeTrue();
});
