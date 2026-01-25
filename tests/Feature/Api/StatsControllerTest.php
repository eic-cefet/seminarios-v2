<?php

use App\Models\Seminar;
use App\Models\Subject;
use App\Models\Workshop;

it('returns correct counts for subjects, seminars and workshops', function () {
    Subject::factory()->count(3)->create();
    Seminar::factory()->count(5)->create();
    Workshop::factory()->count(2)->create();

    $response = $this->getJson('/api/stats');

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'subjects' => 3,
                'seminars' => 5,
                'workshops' => 2,
            ],
        ]);
});

it('returns zeros when no data exists', function () {
    $response = $this->getJson('/api/stats');

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'subjects' => 0,
                'seminars' => 0,
                'workshops' => 0,
            ],
        ]);
});
