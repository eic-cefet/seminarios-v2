<?php

use App\Models\Rating;
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

it('lists pending evaluations for a user who attended a recent seminar', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Banco de Dados Modernos',
        'scheduled_at' => now()->subDays(3),
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
    ]);

    $page = visit('/avaliar');

    $page->assertSee('Banco de Dados Modernos')
        ->assertSee('Avaliar');
});

it('submits a rating with comment and persists it', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Arquitetura de Microsserviços',
        'scheduled_at' => now()->subDays(5),
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
    ]);

    $page = visit('/avaliar');

    $page->assertSee('Arquitetura de Microsserviços')
        ->click('Avaliar')
        ->assertSee('Como voce avalia este seminario?')
        ->click('fieldset button:nth-of-type(5)')
        ->fill('textarea[maxlength="1000"]', 'Excelente conteúdo e didática.')
        ->check('input[type="checkbox"]')
        ->click('Enviar avaliacao')
        ->assertSee('Nenhuma avaliacao pendente');

    $rating = Rating::query()
        ->where('user_id', $user->id)
        ->where('seminar_id', $seminar->id)
        ->first();

    expect($rating)->not->toBeNull()
        ->and($rating->score)->toBe(5)
        ->and($rating->comment)->toBe('Excelente conteúdo e didática.')
        ->and($rating->ai_analysis_consent)->toBeTrue();
});

it('hides seminars outside the evaluation window', function () {
    $user = User::factory()->student()->create();
    $this->actingAs($user);

    $seminar = Seminar::factory()->create([
        'name' => 'Compiladores Avançados',
        'scheduled_at' => now()->subDays(60),
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
    ]);

    $page = visit('/avaliar');

    $page->assertSee('Nenhuma avaliacao pendente')
        ->assertDontSee('Compiladores Avançados');
});
