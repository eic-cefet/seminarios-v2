<?php

use App\Models\User;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('forces an OAuth-like user with a single-word name to complete their profile', function () {
    $user = User::factory()->create(['name' => 'User']);
    $this->actingAs($user);

    $page = visit('/topicos');

    $page->assertSee('Complete seu cadastro')
        ->fill('Nome completo', 'Maria Silva')
        ->click('Continuar')
        ->assertPathIs('/');

    expect($user->fresh()->name)->toBe('Maria Silva');
});
