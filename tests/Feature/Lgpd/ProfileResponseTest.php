<?php

use App\Models\User;

it('includes anonymization_requested_at in the /api/profile payload', function () {
    $user = User::factory()->create([
        'anonymization_requested_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $response = $this->getJson('/api/profile');

    $response->assertSuccessful();
    expect($response->json('user.anonymization_requested_at'))->not->toBeNull();
});

it('returns null when no deletion is pending', function () {
    $user = User::factory()->create(['anonymization_requested_at' => null]);
    $this->actingAs($user);

    $response = $this->getJson('/api/profile');
    expect($response->json('user.anonymization_requested_at'))->toBeNull();
});
