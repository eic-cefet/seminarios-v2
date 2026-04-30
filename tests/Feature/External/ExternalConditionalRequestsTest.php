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

it('returns Last-Modified on show endpoints', function () {
    actingAsAdmin();
    $loc = \App\Models\SeminarLocation::factory()->create();

    $response = $this->getJson("/api/external/v1/locations/{$loc->id}");
    expect($response->headers->get('Last-Modified'))->not->toBeNull();
});

it('returns 304 to If-Modified-Since on show', function () {
    actingAsAdmin();
    $loc = \App\Models\SeminarLocation::factory()->create();

    $first = $this->getJson("/api/external/v1/locations/{$loc->id}");
    $lm = $first->headers->get('Last-Modified');

    $this->withHeader('If-Modified-Since', $lm)
        ->getJson("/api/external/v1/locations/{$loc->id}")
        ->assertStatus(304);
});

it('returns Last-Modified on index endpoints', function () {
    actingAsAdmin();
    \App\Models\SeminarLocation::factory()->count(2)->create();

    $response = $this->getJson('/api/external/v1/locations');
    expect($response->headers->get('Last-Modified'))->not->toBeNull();
});
