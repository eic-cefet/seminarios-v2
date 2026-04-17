<?php

use App\Models\SeminarType;

it('throttles public endpoints after 120 requests per minute', function () {
    SeminarType::factory()->create();

    for ($i = 0; $i < 120; $i++) {
        $this->getJson('/api/seminar-types')->assertOk();
    }

    $this->getJson('/api/seminar-types')->assertStatus(429);
});
