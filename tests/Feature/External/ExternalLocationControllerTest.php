<?php

use App\Models\SeminarLocation;

describe('GET /api/external/v1/locations', function () {
    it('returns all locations ordered by name', function () {
        actingAsAdmin();

        SeminarLocation::factory()->create(['name' => 'Sala B']);
        SeminarLocation::factory()->create(['name' => 'Sala A']);

        $response = $this->getJson('/api/external/v1/locations');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('name');
        expect($names->first())->toBe('Sala A');
        expect($names->last())->toBe('Sala B');
    });

    it('returns location with max_vacancies', function () {
        actingAsAdmin();
        SeminarLocation::factory()->create(['name' => 'Auditório', 'max_vacancies' => 200]);

        $response = $this->getJson('/api/external/v1/locations');

        $response->assertSuccessful();
        expect($response->json('data.0.max_vacancies'))->toBe(200);
    });

    it('returns the canonical envelope on the locations index', function () {
        actingAsAdmin();
        SeminarLocation::factory()->count(2)->create();

        $response = $this->getJson('/api/external/v1/locations');

        $response->assertSuccessful()
            ->assertJsonStructure(['data', 'meta' => ['total']])
            ->assertJsonMissingPath('links');
        expect($response->json('meta.total'))->toBe(2);
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/external/v1/locations')->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->getJson('/api/external/v1/locations')->assertForbidden();
    });
});

describe('GET /api/external/v1/locations/{id}', function () {
    it('returns a single location', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create(['name' => 'Sala 101', 'max_vacancies' => 30]);

        $response = $this->getJson("/api/external/v1/locations/{$location->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $location->id)
            ->assertJsonPath('data.name', 'Sala 101')
            ->assertJsonPath('data.max_vacancies', 30);
    });

    it('returns 404 for non-existent location', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/locations/999')->assertNotFound();
    });
});

describe('POST /api/external/v1/locations', function () {
    it('creates a location', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/locations', [
            'name' => 'Sala Remota',
            'max_vacancies' => 100,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Sala Remota')
            ->assertJsonPath('data.max_vacancies', 100);
        expect(SeminarLocation::where('name', 'Sala Remota')->exists())->toBeTrue();
    });

    it('rejects duplicate name', function () {
        actingAsAdmin();
        SeminarLocation::factory()->create(['name' => 'Sala 101']);

        $response = $this->postJson('/api/external/v1/locations', [
            'name' => 'Sala 101',
            'max_vacancies' => 50,
        ]);

        $response->assertStatus(422);
    });

    it('validates required fields', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/locations', [])->assertStatus(422);
    });

    it('validates max_vacancies is positive', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/locations', [
            'name' => 'Test',
            'max_vacancies' => 0,
        ]);

        $response->assertStatus(422);
    });
});

describe('PUT /api/external/v1/locations/{id}', function () {
    it('updates a location name', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create(['name' => 'Old', 'max_vacancies' => 50]);

        $response = $this->putJson("/api/external/v1/locations/{$location->id}", [
            'name' => 'New Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.max_vacancies', 50);
    });

    it('updates max_vacancies only', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create(['name' => 'Sala', 'max_vacancies' => 50]);

        $response = $this->putJson("/api/external/v1/locations/{$location->id}", [
            'max_vacancies' => 200,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Sala')
            ->assertJsonPath('data.max_vacancies', 200);
    });

    it('rejects duplicate name on update', function () {
        actingAsAdmin();
        SeminarLocation::factory()->create(['name' => 'Existing']);
        $location = SeminarLocation::factory()->create(['name' => 'Other']);

        $response = $this->putJson("/api/external/v1/locations/{$location->id}", [
            'name' => 'Existing',
        ]);

        $response->assertStatus(422);
    });

    it('allows keeping the same name', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create(['name' => 'Sala 101']);

        $response = $this->putJson("/api/external/v1/locations/{$location->id}", [
            'name' => 'Sala 101',
        ]);

        $response->assertSuccessful();
    });
});

describe('policy enforcement', function () {
    it('denies a teacher from listing locations even with the right ability', function () {
        $teacher = \App\Models\User::factory()->teacher()->create();
        $token = $teacher->createToken('t', ['locations:read'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/external/v1/locations')
            ->assertForbidden();
    });
});
