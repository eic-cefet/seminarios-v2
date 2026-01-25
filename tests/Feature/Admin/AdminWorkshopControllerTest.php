<?php

use App\Models\Seminar;
use App\Models\Workshop;

describe('GET /api/admin/workshops', function () {
    it('returns paginated list of workshops for admin', function () {
        actingAsAdmin();

        Workshop::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/workshops');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'seminars_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/workshops');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/workshops');

        $response->assertForbidden();
    });

    it('returns 403 for teacher user', function () {
        actingAsTeacher();

        $response = $this->getJson('/api/admin/workshops');

        $response->assertForbidden();
    });

    it('filters workshops by search term', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Machine Learning Workshop']);
        Workshop::factory()->create(['name' => 'Web Development Workshop']);

        $response = $this->getJson('/api/admin/workshops?search=Machine');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Machine Learning Workshop');
    });

    it('orders workshops by name', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Zebra Workshop']);
        Workshop::factory()->create(['name' => 'Alpha Workshop']);
        Workshop::factory()->create(['name' => 'Beta Workshop']);

        $response = $this->getJson('/api/admin/workshops');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Alpha Workshop');
        expect($data[1]['name'])->toBe('Beta Workshop');
        expect($data[2]['name'])->toBe('Zebra Workshop');
    });
});

describe('GET /api/admin/workshops/{id}', function () {
    it('returns workshop by id for admin', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create(['name' => 'Test Workshop']);

        $response = $this->getJson("/api/admin/workshops/{$workshop->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $workshop->id)
            ->assertJsonPath('data.name', 'Test Workshop');
    });

    it('returns 404 for non-existent workshop', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/workshops/99999');

        $response->assertNotFound();
    });

    it('includes associated seminars', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        $seminar = Seminar::factory()->create(['workshop_id' => $workshop->id]);

        $response = $this->getJson("/api/admin/workshops/{$workshop->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.seminars_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'seminars' => [
                        '*' => ['id', 'name', 'slug'],
                    ],
                ],
            ]);
    });
});

describe('POST /api/admin/workshops', function () {
    it('creates a new workshop for admin', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/workshops', [
            'name' => 'Novo Workshop',
            'description' => 'Descrição do workshop',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Workshop criado com sucesso')
            ->assertJsonPath('data.name', 'Novo Workshop')
            ->assertJsonPath('data.description', 'Descrição do workshop');

        expect(Workshop::where('name', 'Novo Workshop')->exists())->toBeTrue();
    });

    it('creates workshop with assigned seminars', function () {
        actingAsAdmin();

        $seminar1 = Seminar::factory()->create(['workshop_id' => null]);
        $seminar2 = Seminar::factory()->create(['workshop_id' => null]);

        $response = $this->postJson('/api/admin/workshops', [
            'name' => 'Workshop com Seminários',
            'seminar_ids' => [$seminar1->id, $seminar2->id],
        ]);

        $response->assertStatus(201);

        $workshop = Workshop::where('name', 'Workshop com Seminários')->first();
        expect($workshop->seminars()->count())->toBe(2);
    });

    it('returns validation error for missing name', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/workshops', [
            'description' => 'Test description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error for duplicate name', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Existing Workshop']);

        $response = $this->postJson('/api/admin/workshops', [
            'name' => 'Existing Workshop',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

describe('PUT /api/admin/workshops/{id}', function () {
    it('updates workshop for admin', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();

        $response = $this->putJson("/api/admin/workshops/{$workshop->id}", [
            'name' => 'Workshop Atualizado',
            'description' => 'Nova descrição',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Workshop atualizado com sucesso')
            ->assertJsonPath('data.name', 'Workshop Atualizado')
            ->assertJsonPath('data.description', 'Nova descrição');
    });

    it('updates assigned seminars', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        $oldSeminar = Seminar::factory()->create(['workshop_id' => $workshop->id]);
        $newSeminar = Seminar::factory()->create(['workshop_id' => null]);

        $response = $this->putJson("/api/admin/workshops/{$workshop->id}", [
            'name' => $workshop->name,
            'seminar_ids' => [$newSeminar->id],
        ]);

        $response->assertSuccessful();

        $workshop->refresh();
        expect($workshop->seminars()->pluck('id')->toArray())->toBe([$newSeminar->id]);

        $oldSeminar->refresh();
        expect($oldSeminar->workshop_id)->toBeNull();
    });

    it('clears assigned seminars when empty array provided', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        $seminar = Seminar::factory()->create(['workshop_id' => $workshop->id]);

        $response = $this->putJson("/api/admin/workshops/{$workshop->id}", [
            'name' => $workshop->name,
            'seminar_ids' => [],
        ]);

        $response->assertSuccessful();

        $workshop->refresh();
        expect($workshop->seminars()->count())->toBe(0);
    });

    it('returns validation error for duplicate name', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Other Workshop']);
        $workshop = Workshop::factory()->create(['name' => 'My Workshop']);

        $response = $this->putJson("/api/admin/workshops/{$workshop->id}", [
            'name' => 'Other Workshop',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('allows updating to same name', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create(['name' => 'My Workshop']);

        $response = $this->putJson("/api/admin/workshops/{$workshop->id}", [
            'name' => 'My Workshop',
        ]);

        $response->assertSuccessful();
    });
});

describe('DELETE /api/admin/workshops/{id}', function () {
    it('deletes workshop without seminars', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();

        $response = $this->deleteJson("/api/admin/workshops/{$workshop->id}");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Workshop excluído com sucesso');

        expect(Workshop::find($workshop->id))->toBeNull();
    });

    it('returns error when workshop has seminars', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        Seminar::factory()->create(['workshop_id' => $workshop->id]);

        $response = $this->deleteJson("/api/admin/workshops/{$workshop->id}");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Este workshop possui seminários associados e não pode ser excluído');

        expect(Workshop::find($workshop->id))->not->toBeNull();
    });
});

describe('GET /api/admin/workshops/search-seminars', function () {
    it('returns unassigned seminars', function () {
        actingAsAdmin();

        $unassigned = Seminar::factory()->create(['workshop_id' => null, 'name' => 'Unassigned']);
        $assigned = Seminar::factory()->create(['workshop_id' => Workshop::factory()->create()->id, 'name' => 'Assigned']);

        $response = $this->getJson('/api/admin/workshops/search-seminars');

        $response->assertSuccessful();
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();
        expect($ids)->toContain($unassigned->id);
        expect($ids)->not->toContain($assigned->id);
    });

    it('includes current workshop seminars when workshop_id provided', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        $workshopSeminar = Seminar::factory()->create(['workshop_id' => $workshop->id, 'name' => 'Workshop Seminar']);
        $unassigned = Seminar::factory()->create(['workshop_id' => null, 'name' => 'Unassigned']);
        $otherWorkshopSeminar = Seminar::factory()->create([
            'workshop_id' => Workshop::factory()->create()->id,
            'name' => 'Other Workshop Seminar',
        ]);

        $response = $this->getJson("/api/admin/workshops/search-seminars?workshop_id={$workshop->id}");

        $response->assertSuccessful();
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();
        expect($ids)->toContain($workshopSeminar->id);
        expect($ids)->toContain($unassigned->id);
        expect($ids)->not->toContain($otherWorkshopSeminar->id);
    });

    it('filters seminars by search term', function () {
        actingAsAdmin();

        Seminar::factory()->create(['workshop_id' => null, 'name' => 'Machine Learning Talk']);
        Seminar::factory()->create(['workshop_id' => null, 'name' => 'Web Development Talk']);

        $response = $this->getJson('/api/admin/workshops/search-seminars?search=Machine');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Machine Learning Talk');
    });

    it('limits results to 20', function () {
        actingAsAdmin();

        Seminar::factory()->count(25)->create(['workshop_id' => null]);

        $response = $this->getJson('/api/admin/workshops/search-seminars');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(20);
    });
});
