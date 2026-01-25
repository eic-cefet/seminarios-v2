<?php

use App\Models\Seminar;
use App\Models\Workshop;

it('returns list of workshops with seminars', function () {
    // Create workshop with seminars
    $workshop = Workshop::factory()->create();
    Seminar::factory()->count(2)->create(['workshop_id' => $workshop->id]);

    // Create workshop without seminars (should not be returned)
    Workshop::factory()->create();

    $response = $this->getJson('/api/workshops');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description'],
            ],
        ]);
});

it('returns empty array when no workshops with seminars exist', function () {
    Workshop::factory()->create();

    $response = $this->getJson('/api/workshops');

    $response->assertSuccessful()
        ->assertJson(['data' => []]);
});

it('shows workshop with its seminars', function () {
    $workshop = Workshop::factory()->create();
    Seminar::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'active' => true,
    ]);

    $response = $this->getJson("/api/workshops/{$workshop->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'seminars',
            ],
        ])
        ->assertJsonCount(2, 'data.seminars');
});

it('returns 404 for non-existent workshop', function () {
    $response = $this->getJson('/api/workshops/999');

    $response->assertNotFound();
});

it('returns paginated seminars for a workshop', function () {
    $workshop = Workshop::factory()->create();
    Seminar::factory()->count(5)->create([
        'workshop_id' => $workshop->id,
        'active' => true,
    ]);

    $response = $this->getJson("/api/workshops/{$workshop->id}/seminars?per_page=3");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'scheduledAt'],
            ],
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
});

it('filters upcoming seminars only when requested', function () {
    $workshop = Workshop::factory()->create();
    Seminar::factory()->past()->create(['workshop_id' => $workshop->id, 'active' => true]);
    Seminar::factory()->upcoming()->create(['workshop_id' => $workshop->id, 'active' => true]);

    $response = $this->getJson("/api/workshops/{$workshop->id}/seminars?upcoming=1");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('excludes inactive seminars from workshop seminars list', function () {
    $workshop = Workshop::factory()->create();
    Seminar::factory()->create(['workshop_id' => $workshop->id, 'active' => true]);
    Seminar::factory()->create(['workshop_id' => $workshop->id, 'active' => false]);

    $response = $this->getJson("/api/workshops/{$workshop->id}/seminars");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});
