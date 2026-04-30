<?php

use App\Models\SeminarLocation;

it('returns an ETag header on a 200 response', function () {
    actingAsAdmin();
    SeminarLocation::factory()->create();

    $response = $this->getJson('/api/external/v1/locations');

    $response->assertSuccessful();
    expect($response->headers->get('ETag'))->toMatch('/^W\/"[0-9a-f]{16,}"$/');
});

it('returns 304 when If-None-Match matches', function () {
    actingAsAdmin();
    SeminarLocation::factory()->create();

    $first = $this->getJson('/api/external/v1/locations');
    $etag = $first->headers->get('ETag');

    $this->withHeader('If-None-Match', $etag)
        ->getJson('/api/external/v1/locations')
        ->assertStatus(304);
});
