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

it('reflects an admin presence toggle on the student profile page', function () {
    $student = User::factory()->student()->create(['name' => 'Lucas Pereira']);
    $seminar = Seminar::factory()->create([
        'name' => 'Banco de Dados',
        'scheduled_at' => now()->subDay(),
    ]);
    Registration::factory()->create([
        'user_id' => $student->id,
        'seminar_id' => $seminar->id,
        'present' => false,
    ]);

    // --- Admin marks the student present (Admin SPA) ---
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $adminPage = visit('/admin/registrations');

    // The presence control is a Radix Switch, which renders a
    // `button[role="switch"]`; `check()` does not drive it, so click the
    // single rendered switch directly. The student/seminar names are
    // factory-provided and therefore drift-proof. The row's persistent
    // presence badge starts at "Nao" and flips to "Sim" after the toggle
    // round-trips (the success toast is transient, so we assert the stable
    // badge instead).
    // Exactly one registration is seeded, so the single role=switch targets it.
    $adminPage->assertNoJavascriptErrors()
        ->assertSee('Lucas Pereira')
        ->assertSee('Banco de Dados')
        ->assertSee('Nao')
        ->click('button[role="switch"]')
        ->assertSee('Sim');

    // --- Student sees the presence reflected (System SPA) ---
    // The per-student "Presente" indicator lives in the profile's
    // "Minhas inscrições" section (/perfil), not the public /apresentacoes
    // catalog (which carries no per-user presence state).
    $this->actingAs($student);

    $studentPage = visit('/perfil');

    $studentPage->assertNoJavascriptErrors()
        ->assertSee('Banco de Dados')
        ->assertSee('Presente');
});
