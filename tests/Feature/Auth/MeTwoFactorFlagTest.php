<?php

use App\Models\User;

it('exposes two_factor_enabled=false by default', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/auth/me')->assertJsonPath('user.two_factor_enabled', false);
});

it('exposes two_factor_enabled=true once confirmed', function () {
    $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
    $this->actingAs($user);

    $this->getJson('/api/auth/me')->assertJsonPath('user.two_factor_enabled', true);
});
