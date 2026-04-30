<?php

use App\Models\SeminarType;

describe('GET /api/external/v1/seminar-types', function () {
    it('returns all seminar types ordered by name', function () {
        actingAsAdmin();

        SeminarType::factory()->create(['name' => 'TCC']);
        SeminarType::factory()->create(['name' => 'Dissertação']);

        $response = $this->getJson('/api/external/v1/seminar-types');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('name');
        expect($names->first())->toBe('Dissertação');
        expect($names->last())->toBe('TCC');
    });

    it('returns the canonical envelope on the seminar-types index', function () {
        actingAsAdmin();
        SeminarType::factory()->count(2)->create();

        $response = $this->getJson('/api/external/v1/seminar-types');

        $response->assertSuccessful()
            ->assertJsonStructure(['data', 'meta' => ['total']])
            ->assertJsonMissingPath('links');
        expect($response->json('meta.total'))->toBe(2);
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/external/v1/seminar-types')->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->getJson('/api/external/v1/seminar-types')->assertForbidden();
    });
});

describe('GET /api/external/v1/seminar-types/{id}', function () {
    it('returns a single seminar type', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'TCC']);

        $response = $this->getJson("/api/external/v1/seminar-types/{$type->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.name', 'TCC');
    });

    it('returns 404 for non-existent type', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/seminar-types/999')->assertNotFound();
    });
});

describe('POST /api/external/v1/seminar-types', function () {
    it('creates a seminar type', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'Workshop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Workshop');
        expect(SeminarType::where('name', 'Workshop')->exists())->toBeTrue();
    });

    it('rejects duplicate name', function () {
        actingAsAdmin();
        SeminarType::factory()->create(['name' => 'TCC']);

        $response = $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'TCC',
        ]);

        $response->assertStatus(422);
    });

    it('validates required name', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/seminar-types', [])->assertStatus(422);
    });
});

describe('PUT /api/external/v1/seminar-types/{id}', function () {
    it('updates a seminar type', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/external/v1/seminar-types/{$type->id}", [
            'name' => 'New Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'New Name');
    });

    it('rejects duplicate name on update', function () {
        actingAsAdmin();
        SeminarType::factory()->create(['name' => 'Existing']);
        $type = SeminarType::factory()->create(['name' => 'Other']);

        $response = $this->putJson("/api/external/v1/seminar-types/{$type->id}", [
            'name' => 'Existing',
        ]);

        $response->assertStatus(422);
    });

    it('allows keeping the same name', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'TCC']);

        $response = $this->putJson("/api/external/v1/seminar-types/{$type->id}", [
            'name' => 'TCC',
        ]);

        $response->assertSuccessful();
    });
});

describe('policy enforcement', function () {
    it('denies a teacher from listing seminar types', function () {
        $teacher = \App\Models\User::factory()->teacher()->create();
        $token = $teacher->createToken('t', ['seminar-types:read'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/external/v1/seminar-types')
            ->assertForbidden();
    });
});
