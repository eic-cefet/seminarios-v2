<?php

use App\Models\SeminarLocation;
use App\Models\User;

describe('Token abilities enforcement', function () {
    it('allows full access token to access all endpoints', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('full', ['*']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/seminars')
            ->assertSuccessful();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/users')
            ->assertSuccessful();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/locations')
            ->assertSuccessful();
    });

    it('allows scoped token to access permitted endpoints', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('scoped', ['seminars:read', 'locations:read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/seminars')
            ->assertSuccessful();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/locations')
            ->assertSuccessful();
    });

    it('blocks scoped token from unpermitted read endpoints', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('seminars-only', ['seminars:read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/users')
            ->assertForbidden();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/locations')
            ->assertForbidden();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/seminar-types')
            ->assertForbidden();
    });

    it('blocks read-only token from write endpoints', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('read-only', ['seminars:read']);
        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();

        $this->withToken($token->plainTextToken)
            ->postJson('/api/external/v1/seminars', [
                'name' => 'Test',
                'scheduled_at' => '2099-01-01T00:00:00',
                'seminar_location_id' => $location->id,
                'subjects' => ['Test'],
                'speaker_ids' => [$speaker->id],
            ])
            ->assertForbidden();
    });

    it('allows write token to create but not read', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('write-only', ['seminars:write', 'locations:read', 'users:read']);
        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();

        // Can create
        $this->withToken($token->plainTextToken)
            ->postJson('/api/external/v1/seminars', [
                'name' => 'Write Test',
                'scheduled_at' => '2099-01-01T00:00:00',
                'seminar_location_id' => $location->id,
                'subjects' => ['Test'],
                'speaker_ids' => [$speaker->id],
            ])
            ->assertCreated();

        // Cannot list
        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/seminars')
            ->assertForbidden();
    });

    it('enforces speaker-data abilities separately', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('users-only', ['users:read', 'users:write']);
        $user = User::factory()->create();

        // Can read user
        $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/users/{$user->id}")
            ->assertSuccessful();

        // Cannot read speaker data
        $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/users/{$user->id}/speaker-data")
            ->assertForbidden();

        // Cannot write speaker data
        $this->withToken($token->plainTextToken)
            ->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
                'institution' => 'Test',
            ])
            ->assertForbidden();
    });

    it('session auth bypasses ability checks', function () {
        actingAsAdmin();

        // Session users don't have token abilities — middleware should pass through
        $this->getJson('/api/external/v1/seminars')->assertSuccessful();
        $this->getJson('/api/external/v1/users')->assertSuccessful();
    });

    it('returns abilities list from admin endpoint', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/api-tokens/abilities');

        $response->assertSuccessful();
        expect($response->json('data'))->toContain('seminars:read');
        expect($response->json('data'))->toContain('seminars:write');
        expect($response->json('data'))->toContain('workshops:read');
        expect($response->json('data'))->toContain('workshops:write');
        expect($response->json('data'))->toContain('users:read');
        expect($response->json('data'))->toContain('speaker-data:write');
        expect($response->json('data'))->toHaveCount(12);
    });

    it('creates token with specific abilities', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Scoped Token',
            'abilities' => ['seminars:read', 'seminars:write'],
        ]);

        $response->assertCreated();
        expect($response->json('data.abilities'))->toBe(['seminars:read', 'seminars:write']);
    });

    it('rejects invalid abilities', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Bad Token',
            'abilities' => ['invalid:ability'],
        ]);

        $response->assertStatus(422);
    });
});
