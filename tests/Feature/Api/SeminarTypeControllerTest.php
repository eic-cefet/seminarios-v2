<?php

use App\Models\SeminarType;

it('returns list of seminar types ordered by name', function () {
    SeminarType::factory()->create(['name' => 'Workshop']);
    SeminarType::factory()->create(['name' => 'Apresentação']);
    SeminarType::factory()->create(['name' => 'Palestra']);

    $response = $this->getJson('/api/seminar-types');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ])
        ->assertJsonPath('data.0.name', 'Apresentação')
        ->assertJsonPath('data.1.name', 'Palestra')
        ->assertJsonPath('data.2.name', 'Workshop');
});

it('returns empty array when no seminar types exist', function () {
    $response = $this->getJson('/api/seminar-types');

    $response->assertSuccessful()
        ->assertJson(['data' => []]);
});
