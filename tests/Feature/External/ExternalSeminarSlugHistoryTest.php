<?php

use App\Models\Seminar;

it('resolves external show through a historical slug', function () {
    actingAsAdmin();
    $seminar = Seminar::factory()->create(['slug' => 'externo-antigo']);
    $seminar->update(['slug' => 'externo-novo']);

    $response = $this->getJson('/api/external/v1/seminars/externo-antigo');

    $response->assertSuccessful();
    expect($response->json('data.slug'))->toBe('externo-novo');
});

it('resolves external update through a historical slug', function () {
    $admin = actingAsAdmin();
    $seminar = Seminar::factory()->create([
        'slug' => 'atualizavel-antigo',
        'created_by' => $admin->id,
    ]);
    $seminar->update(['slug' => 'atualizavel-novo']);

    $response = $this->patchJson('/api/external/v1/seminars/atualizavel-antigo', [
        'description' => 'Atualizado via slug antigo',
    ]);

    $response->assertSuccessful();
    expect($seminar->fresh()->description)->toBe('Atualizado via slug antigo');
});

it('returns 404 externally for a historical slug of a trashed seminar', function () {
    actingAsAdmin();
    $seminar = Seminar::factory()->create(['slug' => 'externo-apagado']);
    $seminar->update(['slug' => 'externo-apagado-2']);
    $seminar->delete();

    $this->getJson('/api/external/v1/seminars/externo-apagado')->assertNotFound();
});
