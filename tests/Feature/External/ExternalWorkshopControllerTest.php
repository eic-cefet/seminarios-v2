<?php

use App\Models\Seminar;
use App\Models\Workshop;

describe('GET /api/external/v1/workshops', function () {
    it('returns all workshops ordered by name', function () {
        actingAsAdmin();

        Workshop::factory()->create(['name' => 'Workshop B']);
        Workshop::factory()->create(['name' => 'Workshop A']);

        $response = $this->getJson('/api/external/v1/workshops');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('name');
        expect($names->first())->toBe('Workshop A');
        expect($names->last())->toBe('Workshop B');
    });

    it('paginates results', function () {
        actingAsAdmin();

        Workshop::factory()->count(20)->create();

        $response = $this->getJson('/api/external/v1/workshops');

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(15);
        expect($response->json('meta.total'))->toBe(20);
    });

    it('includes seminars_count', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        Seminar::factory()->count(3)->create(['workshop_id' => $workshop->id]);

        $response = $this->getJson('/api/external/v1/workshops');

        $response->assertSuccessful();
        expect($response->json('data.0.seminars_count'))->toBe(3);
    });

    it('filters by search query', function () {
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

describe('GET /api/external/v1/workshops filters & sort', function () {
    it('rejects an invalid sort column', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/workshops?sort=password')
            ->assertStatus(422);
    });

    it('rejects malformed updated_since', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/workshops?updated_since=not-a-date')
            ->assertStatus(422);
    });

    it('sorts by name descending with leading dash', function () {
        actingAsAdmin();
        Workshop::factory()->create(['name' => 'Workshop A']);
        Workshop::factory()->create(['name' => 'Workshop B']);

        $names = collect($this->getJson('/api/external/v1/workshops?sort=-name')->json('data'))->pluck('name');
        expect($names->first())->toBe('Workshop B');
    });

    it('filters by updated_since', function () {
        actingAsAdmin();
        $stale = Workshop::factory()->create();
        $stale->updated_at = now()->subDays(7);
        $stale->saveQuietly();
        $fresh = Workshop::factory()->create();

        $ids = collect($this->getJson('/api/external/v1/workshops?updated_since='.now()->subDay()->toIso8601String())->json('data'))->pluck('id');
        expect($ids)->toContain($fresh->id)->and($ids)->not->toContain($stale->id);
    });
});

describe('GET /api/external/v1/workshops/{workshop}', function () {
    it('returns a single workshop', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Deep Learning']);

        $response = $this->getJson("/api/external/v1/workshops/{$workshop->slug}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $workshop->id)
            ->assertJsonPath('data.name', 'Deep Learning');
    });

    it('returns 404 for non-existent workshop', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/workshops/non-existent-slug')->assertNotFound();
    });

    it('resolves a workshop by slug, not id', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Distributed Systems']);

        $this->getJson("/api/external/v1/workshops/{$workshop->slug}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $workshop->id)
            ->assertJsonPath('data.slug', $workshop->slug);
    });
});

describe('POST /api/external/v1/workshops', function () {
    it('creates a workshop', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/workshops', [
            'name' => 'AI Workshop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'AI Workshop')
            ->assertJsonPath('data.slug', 'ai-workshop');
        expect(Workshop::where('name', 'AI Workshop')->exists())->toBeTrue();
    });

    it('creates a workshop with description', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/workshops', [
            'name' => 'AI Workshop',
            'description' => 'A workshop about AI',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.description', 'A workshop about AI');
    });

    it('rejects duplicate name', function () {
        actingAsAdmin();
        Workshop::factory()->create(['name' => 'AI Workshop']);

        $response = $this->postJson('/api/external/v1/workshops', [
            'name' => 'AI Workshop',
        ]);

        $response->assertStatus(422);
    });

    it('validates required fields', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/workshops', [])->assertStatus(422);
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->postJson('/api/external/v1/workshops', ['name' => 'Test'])->assertForbidden();
    });
});

describe('PUT /api/external/v1/workshops/{workshop}', function () {
    it('updates a workshop name', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'New Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.slug', 'new-name');
    });

    it('updates description only', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Workshop']);

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

        $response = $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'Existing',
        ]);

        $response->assertStatus(422);
    });

    it('allows keeping the same name', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create(['name' => 'Workshop A']);

        $response = $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'Workshop A',
        ]);

        $response->assertSuccessful();
    });

    it('returns 404 for non-existent workshop', function () {
        actingAsAdmin();
        $this->putJson('/api/external/v1/workshops/non-existent-slug', [
            'name' => 'Test',
        ])->assertNotFound();
    });
});
