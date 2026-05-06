<?php

use App\Enums\Gender;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;

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

    it('returns gender and name_plural in the index response', function () {
        actingAsAdmin();
        SeminarType::factory()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
            'gender' => Gender::Feminine,
        ]);

        $response = $this->getJson('/api/external/v1/seminar-types');

        $response->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Dissertação',
                'name_plural' => 'Dissertações',
                'gender' => 'feminine',
            ]);
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

    it('accepts gender and name_plural on store and persists them', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'Tese',
            'name_plural' => 'Teses',
            'gender' => 'feminine',
        ]);

        $response->assertSuccessful()->assertJsonFragment([
            'name' => 'Tese',
            'name_plural' => 'Teses',
            'gender' => 'feminine',
        ]);

        expect(SeminarType::query()->where('name', 'Tese')->first())
            ->name_plural->toBe('Teses')
            ->gender->toBe(Gender::Feminine);
    });

    it('defaults gender to masculine when not provided on store', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'Banca',
        ]);

        $response->assertSuccessful();
        expect(SeminarType::query()->where('name', 'Banca')->first()->gender)
            ->toBe(Gender::Masculine);
    });

    it('rejects invalid gender values on store', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'X',
            'gender' => 'neuter',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['gender']);
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

    it('updates gender and name_plural', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->masculine()->create(['name' => 'X', 'name_plural' => null]);

        $response = $this->patchJson('/api/external/v1/seminar-types/'.$type->id, [
            'name' => 'X',
            'gender' => 'feminine',
            'name_plural' => 'Xs',
        ]);

        $response->assertSuccessful();
        expect($type->fresh())
            ->gender->toBe(Gender::Feminine)
            ->name_plural->toBe('Xs');
    });
});

describe('DELETE /api/external/v1/seminar-types/{id}', function () {
    it('deletes a seminar type', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create();

        $this->deleteJson("/api/external/v1/seminar-types/{$type->id}")
            ->assertSuccessful()
            ->assertJsonPath('message', 'Seminar type deleted successfully.');

        expect(SeminarType::find($type->id))->toBeNull();
    });

    it('returns 409 when seminar type is in use by a seminar', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create();
        Seminar::factory()->create(['seminar_type_id' => $type->id]);

        $this->deleteJson("/api/external/v1/seminar-types/{$type->id}")
            ->assertStatus(409)
            ->assertJsonPath('error', 'seminar_type_in_use');

        expect(SeminarType::find($type->id))->not->toBeNull();
    });

    it('returns 404 for non-existent seminar type', function () {
        actingAsAdmin();
        $this->deleteJson('/api/external/v1/seminar-types/999999')->assertNotFound();
    });

    it('returns 401 for unauthenticated user', function () {
        $type = SeminarType::factory()->create();
        $this->deleteJson("/api/external/v1/seminar-types/{$type->id}")->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $type = SeminarType::factory()->create();
        $this->deleteJson("/api/external/v1/seminar-types/{$type->id}")->assertForbidden();
    });

    it('requires seminar-types:delete ability', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('t', ['seminar-types:read'])->plainTextToken;
        $type = SeminarType::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/external/v1/seminar-types/{$type->id}")
            ->assertForbidden();
    });

    it('allows token with seminar-types:delete ability', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('t', ['seminar-types:delete'])->plainTextToken;
        $type = SeminarType::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/external/v1/seminar-types/{$type->id}")
            ->assertSuccessful();
    });
});

describe('policy enforcement', function () {
    it('denies a teacher from listing seminar types', function () {
        $teacher = User::factory()->teacher()->create();
        $token = $teacher->createToken('t', ['seminar-types:read'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/external/v1/seminar-types')
            ->assertForbidden();
    });
});

describe('GET /api/external/v1/seminar-types sparse fieldsets', function () {
    it('returns only requested fields with ?fields on show', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create();

        $payload = $this->getJson("/api/external/v1/seminar-types/{$type->id}?fields=id")
            ->assertSuccessful()
            ->json('data');

        expect(array_keys($payload))->toBe(['id']);
    });

    it('returns 422 on unknown field name', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create();

        $this->getJson("/api/external/v1/seminar-types/{$type->id}?fields=password")
            ->assertStatus(422);
    });
});
