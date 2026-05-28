<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('lets an authenticated user register for and unregister from a seminar', function () {
    $user = User::factory()->student()->create(['name' => 'Mariana Estudante']);
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Inteligência Artificial Aplicada',
        'scheduled_at' => now()->addDays(7),
    ]);

    $page = visit("/seminario/{$seminar->slug}");

    // Wait for the authenticated user to load (the navbar renders their name)
    // before clicking. handleRegisterClick() opens the login modal when `user`
    // is still null, so clicking before /auth/me resolves silently fails to
    // register — a race that only surfaces under CI load.
    $page->assertSee('Inteligência Artificial Aplicada')
        ->assertSee('Mariana Estudante')
        ->click('Realizar inscrição')
        ->assertSee('Você está inscrito!');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())
        ->toBeTrue();

    $page->click('Cancelar inscrição')
        ->assertSee('Realizar inscrição');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())
        ->toBeFalse();
});

it('opens the login modal when an unauthenticated visitor clicks register', function () {
    $seminar = Seminar::factory()->create([
        'scheduled_at' => now()->addDays(7),
    ]);

    $page = visit("/seminario/{$seminar->slug}");

    $page->click('Realizar inscrição')
        ->assertSee('Entrar')
        ->assertSee('Entre com sua conta para acessar o sistema');
});
