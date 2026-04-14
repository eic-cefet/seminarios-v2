<?php

use App\Models\Workshop;

describe('GET /api/external/v1/workshops', function () {
    it('returns workshops ordered by name', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Workshop B']);
        Workshop::factory()->create(['name' => 'Workshop A']);

        $response = $this->getJson('/api/external/v1/workshops');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('name');
        expect($names->first())->toBe('Workshop A');
        expect($names->last())->toBe('Workshop B');
    });

    it('returns workshops with seminars_count', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create();

        $response = $this->getJson('/api/external/v1/workshops');

        $response->assertSuccessful();
        expect($response->json('data.0.seminars_count'))->toBe(0);
    });

    it('filters workshops by search', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Machine Learning']);
        Workshop::factory()->create(['name' => 'Web Development']);

        $response = $this->getJson('/api/external/v1/workshops?search=Machine');

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Machine Learning');
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/external/v1/workshops')->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->getJson('/api/external/v1/workshops')->assertForbidden();
    });
});

describe('GET /api/external/v1/workshops/{slug}', function () {
    it('returns a single workshop', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'AI Workshop']);

        $response = $this->getJson("/api/external/v1/workshops/{$workshop->slug}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $workshop->id)
            ->assertJsonPath('data.name', 'AI Workshop')
            ->assertJsonPath('data.slug', $workshop->slug);
    });

    it('returns 404 for non-existent workshop', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/workshops/non-existent')->assertNotFound();
    });
});

describe('POST /api/external/v1/workshops', function () {
    it('creates a workshop', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/workshops', [
            'name' => 'New Workshop',
            'description' => 'A great workshop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Workshop')
            ->assertJsonPath('data.description', 'A great workshop');
        expect(Workshop::where('name', 'New Workshop')->exists())->toBeTrue();
    });

    it('creates a workshop without description', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/workshops', [
            'name' => 'Minimal Workshop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Minimal Workshop')
            ->assertJsonPath('data.description', null);
    });

    it('rejects duplicate name', function () {
        actingAsAdmin();
        Workshop::factory()->create(['name' => 'Existing']);

        $this->postJson('/api/external/v1/workshops', [
            'name' => 'Existing',
        ])->assertStatus(422);
    });

    it('validates required fields', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/workshops', [])->assertStatus(422);
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->postJson('/api/external/v1/workshops', [
            'name' => 'Test',
        ])->assertForbidden();
    });
});

describe('PUT /api/external/v1/workshops/{slug}', function () {
    it('updates a workshop name', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'New Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'New Name');
    });

    it('updates description only', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Workshop', 'description' => 'Old']);

        $response = $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'description' => 'Updated description',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Workshop')
            ->assertJsonPath('data.description', 'Updated description');
    });

    it('rejects duplicate name on update', function () {
        actingAsAdmin();
        Workshop::factory()->create(['name' => 'Existing']);
        $workshop = Workshop::factory()->create(['name' => 'Other']);

        $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'Existing',
        ])->assertStatus(422);
    });

    it('allows keeping the same name', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Same Name']);

        $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'Same Name',
        ])->assertSuccessful();
    });

    it('returns 404 for non-existent workshop', function () {
        actingAsAdmin();
        $this->putJson('/api/external/v1/workshops/non-existent', [
            'name' => 'Test',
        ])->assertNotFound();
    });
});
