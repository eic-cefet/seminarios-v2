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

    $isRegistered = fn () => Registration::where('user_id', $user->id)
        ->where('seminar_id', $seminar->id)
        ->exists();

    $page = visit("/seminario/{$seminar->slug}");

    // Wait for the authenticated user to load (the navbar renders their name)
    // before clicking: handleRegisterClick() opens the login modal when `user`
    // is still null, so a click before /auth/me resolves would fail to register.
    $page->assertSee('Inteligência Artificial Aplicada')
        ->assertSee('Mariana Estudante')
        ->click('Realizar inscrição');

    // The in-process test server (amphp, single event loop) can drop the
    // register POST's response under concurrent load even though the row
    // commits. Await the committed state (yielding so the server can run),
    // then confirm the UI via a fresh GET-driven page load.
    waitForServer($page, $isRegistered);
    expect($isRegistered())->toBeTrue();

    $page = visit("/seminario/{$seminar->slug}");
    $page->assertSee('Você está inscrito!')
        ->click('Cancelar inscrição');

    waitForServer($page, fn () => ! $isRegistered());
    expect($isRegistered())->toBeFalse();

    visit("/seminario/{$seminar->slug}")->assertSee('Realizar inscrição');
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
