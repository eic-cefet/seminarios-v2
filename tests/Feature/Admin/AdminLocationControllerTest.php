<?php

use App\Models\Seminar;
use App\Models\SeminarLocation;

describe('GET /api/admin/locations', function () {
    it('returns paginated list of locations for admin', function () {
        actingAsAdmin();

        SeminarLocation::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/locations');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'max_vacancies', 'seminars_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/locations');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/locations');

        $response->assertForbidden();
    });

    it('returns 403 for teacher user', function () {
        actingAsTeacher();

        $response = $this->getJson('/api/admin/locations');

        $response->assertForbidden();
    });
});

describe('GET /api/admin/locations/{id}', function () {
    it('returns location by id for admin', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create(['name' => 'Sala 101']);

        $response = $this->getJson("/api/admin/locations/{$location->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $location->id)
            ->assertJsonPath('data.name', 'Sala 101');
    });

    it('returns 404 for non-existent location', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/locations/99999');

        $response->assertNotFound();
    });
});

describe('POST /api/admin/locations', function () {
    it('creates a new location for admin', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/locations', [
            'name' => 'Nova Sala',
            'max_vacancies' => 50,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Local criado com sucesso')
            ->assertJsonPath('data.name', 'Nova Sala')
            ->assertJsonPath('data.max_vacancies', 50);

        expect(SeminarLocation::where('name', 'Nova Sala')->exists())->toBeTrue();
    });

    it('returns validation error for missing name', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/locations', [
            'max_vacancies' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error for missing max_vacancies', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/locations', [
            'name' => 'Nova Sala',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_vacancies']);
    });

    it('returns validation error for invalid max_vacancies', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/locations', [
            'name' => 'Nova Sala',
            'max_vacancies' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_vacancies']);
    });
});

describe('PUT /api/admin/locations/{id}', function () {
    it('updates location for admin', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();

        $response = $this->putJson("/api/admin/locations/{$location->id}", [
            'name' => 'Sala Atualizada',
            'max_vacancies' => 100,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Local atualizado com sucesso')
            ->assertJsonPath('data.name', 'Sala Atualizada')
            ->assertJsonPath('data.max_vacancies', 100);
    });

    it('allows partial update', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create([
            'name' => 'Sala Original',
            'max_vacancies' => 50,
        ]);

        $response = $this->putJson("/api/admin/locations/{$location->id}", [
            'name' => 'Sala Renomeada',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Sala Renomeada')
            ->assertJsonPath('data.max_vacancies', 50);
    });
});

describe('DELETE /api/admin/locations/{id}', function () {
    it('deletes location for admin', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();

        $response = $this->deleteJson("/api/admin/locations/{$location->id}");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Local excluído com sucesso');

        expect(SeminarLocation::find($location->id))->toBeNull();
    });

    it('returns error when deleting location with seminars', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();
        Seminar::factory()->create(['seminar_location_id' => $location->id]);

        $response = $this->deleteJson("/api/admin/locations/{$location->id}");

        // FK constraint prevents deleting locations with seminars
        $response->assertStatus(500);

        expect(SeminarLocation::find($location->id))->not->toBeNull();
    });
});
