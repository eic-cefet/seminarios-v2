<?php

use App\Models\User;

it('forces an OAuth-like user with a single-word name to complete their profile', function () {
    $user = User::factory()->create(['name' => 'User']);
    $this->actingAs($user);

    $page = visit('/topicos');

    $page->assertSee('Complete seu cadastro')
        ->fill('Nome completo', 'Maria Silva')
        ->click('Continuar')
        ->assertPathIs('/');

    expect($user->fresh()->name)->toBe('Maria Silva');
})->skip('Requires pestphp/pest-plugin-browser + Playwright (not installed in this repo).');
