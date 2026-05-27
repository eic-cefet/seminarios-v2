<?php

use App\Models\User;

it('rejects profile updates that fail the full-name rule', function () {
    actingAsUser();

    $response = $this->putJson('/api/profile', [
        'name' => 'Maria',
        'email' => 'maria@example.test',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});

it('accepts profile updates with a valid full name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $this->actingAs($user);

    $response = $this->putJson('/api/profile', [
        'name' => 'Maria Silva',
        'email' => $user->email,
    ]);

    $response->assertSuccessful();
    expect($user->fresh()->name)->toBe('Maria Silva');
});
