<?php

use App\Models\Course;

it('returns list of courses ordered by name', function () {
    // Create courses in non-alphabetical order
    Course::factory()->create(['name' => 'Engenharia de Computação']);
    Course::factory()->create(['name' => 'Análise de Sistemas']);
    Course::factory()->create(['name' => 'Ciência da Computação']);

    $response = $this->getJson('/api/courses');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ])
        ->assertJsonPath('data.0.name', 'Análise de Sistemas')
        ->assertJsonPath('data.1.name', 'Ciência da Computação')
        ->assertJsonPath('data.2.name', 'Engenharia de Computação');
});

it('returns empty array when no courses exist', function () {
    $response = $this->getJson('/api/courses');

    $response->assertSuccessful()
        ->assertJson(['data' => []]);
});
