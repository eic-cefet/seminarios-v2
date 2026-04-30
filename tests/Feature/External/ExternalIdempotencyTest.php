<?php

use App\Models\SeminarLocation;

it('replays the original response on a retry with the same key and body', function () {
    actingAsAdmin();
    $body = ['name' => 'Sala Idempotente', 'max_vacancies' => 50];

    $first = $this->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $first->assertCreated();

    $second = $this->postJson('/api/external/v1/locations', $body, ['Idempotency-Key' => 'k-1']);
    $second->assertStatus($first->getStatusCode());
    expect($second->getContent())->toBe($first->getContent());
    expect(SeminarLocation::where('name', 'Sala Idempotente')->count())->toBe(1);
});

it('returns 409 on retry with same key but different body', function () {
    actingAsAdmin();
    $this->postJson('/api/external/v1/locations', ['name' => 'A', 'max_vacancies' => 10], ['Idempotency-Key' => 'k-2'])->assertCreated();

    $this->postJson('/api/external/v1/locations', ['name' => 'B', 'max_vacancies' => 20], ['Idempotency-Key' => 'k-2'])
        ->assertStatus(409)
        ->assertJsonPath('error', 'idempotency_key_conflict');
});

it('rejects malformed Idempotency-Key', function () {
    actingAsAdmin();
    $this->postJson('/api/external/v1/locations', [], ['Idempotency-Key' => str_repeat('x', 300)])
        ->assertStatus(422)
        ->assertJsonPath('error', 'validation_error');
});
