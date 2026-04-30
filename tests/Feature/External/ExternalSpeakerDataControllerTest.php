<?php

use App\Models\User;
use App\Models\UserSpeakerData;

describe('GET /api/external/v1/users/{id}/speaker-data', function () {
    it('returns speaker data for a user', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();

        $response = $this->getJson("/api/external/v1/users/{$user->id}/speaker-data");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => ['id', 'slug', 'institution', 'description'],
            ]);
    });

    it('returns null data when user has no speaker data', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->getJson("/api/external/v1/users/{$user->id}/speaker-data");

        $response->assertSuccessful();
        expect($response->json('data'))->toBeNull();
    });

    it('returns 401 for unauthenticated user', function () {
        $user = User::factory()->create();
        $this->getJson("/api/external/v1/users/{$user->id}/speaker-data")->assertUnauthorized();
    });
});

describe('PUT /api/external/v1/users/{id}/speaker-data', function () {
    it('creates speaker data for a user without one', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
            'institution' => 'CEFET-RJ',
            'description' => 'Professor',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.institution', 'CEFET-RJ')
            ->assertJsonPath('data.description', 'Professor');

        expect(UserSpeakerData::where('user_id', $user->id)->exists())->toBeTrue();
    });

    it('updates existing speaker data', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();

        $response = $this->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
            'institution' => 'MIT',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.institution'))->toBe('MIT');
    });

    it('clears speaker data fields with null', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();
        $user->speakerData->update(['institution' => 'Old']);

        $response = $this->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
            'institution' => null,
        ]);

        $response->assertSuccessful();
        expect($response->json('data.institution'))->toBeNull();
    });

    it('generates slug on first creation', function () {
        actingAsAdmin();
        $user = User::factory()->create(['name' => 'João Silva']);

        $response = $this->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
            'institution' => 'CEFET-RJ',
        ]);

        expect($response->json('data.slug'))->toContain('joao-silva');
    });
});

describe('GET /api/external/v1/users/{id}/speaker-data sparse fieldsets', function () {
    it('returns only requested fields with ?fields on show', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();

        $payload = $this->getJson("/api/external/v1/users/{$user->id}/speaker-data?fields=id")
            ->assertSuccessful()
            ->json('data');

        expect(array_keys($payload))->toBe(['id']);
    });

    it('returns 422 on unknown field name', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();

        $this->getJson("/api/external/v1/users/{$user->id}/speaker-data?fields=password")
            ->assertStatus(422);
    });
});
