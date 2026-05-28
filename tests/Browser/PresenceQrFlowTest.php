<?php

use App\Models\PresenceLink;
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

it('registers presence automatically when an authenticated user opens a valid QR link', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Inteligência Artificial Aplicada',
        'scheduled_at' => now()->addDays(7),
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => false,
    ]);
    $link = PresenceLink::factory()->create([
        'seminar_id' => $seminar->id,
        'active' => true,
    ]);

    $page = visit("/p/{$link->uuid}");

    $page->assertSee('Presença Registrada!')
        ->assertSee('Inteligência Artificial Aplicada');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->first()->present)
        ->toBeTrue();
});

it('creates a registration marked present when none exists yet', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Computação Quântica',
        'scheduled_at' => now()->addDays(3),
    ]);
    $link = PresenceLink::factory()->create([
        'seminar_id' => $seminar->id,
        'active' => true,
    ]);

    $page = visit("/p/{$link->uuid}");

    $page->assertSee('Presença Registrada!');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->first()?->present)
        ->toBeTrue();
});

it('is idempotent — a second scan after being present still succeeds', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Redes Neurais Profundas',
        'scheduled_at' => now()->addDays(2),
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
    ]);
    $link = PresenceLink::factory()->create([
        'seminar_id' => $seminar->id,
        'active' => true,
    ]);

    $page = visit("/p/{$link->uuid}");

    $page->assertSee('Presença Registrada!');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->count())
        ->toBe(1)
        ->and(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->first()->present)
        ->toBeTrue();
});

it('prompts an anonymous visitor to authenticate instead of registering presence', function () {
    $seminar = Seminar::factory()->create([
        'name' => 'Engenharia de Software',
        'scheduled_at' => now()->addDays(5),
    ]);
    $link = PresenceLink::factory()->create([
        'seminar_id' => $seminar->id,
        'active' => true,
    ]);

    $page = visit("/p/{$link->uuid}");

    $page->assertSee('Registrar Presença')
        ->assertSee('Você precisa estar autenticado para registrar presença')
        ->assertSee('Entrar na conta');

    expect(Registration::where('seminar_id', $seminar->id)->count())->toBe(0);
});

it('shows an error for an inactive link', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create(['name' => 'Sistemas Distribuídos']);
    $link = PresenceLink::factory()->inactive()->create([
        'seminar_id' => $seminar->id,
    ]);

    $page = visit("/p/{$link->uuid}");

    $page->assertSee('Link Inválido');

    expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())
        ->toBeFalse();
});

it('shows an error for an unknown link uuid', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $page = visit('/p/00000000-0000-0000-0000-000000000000');

    $page->assertSee('Link Inválido');
});
