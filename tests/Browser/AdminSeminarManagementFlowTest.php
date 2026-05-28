<?php

use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\User;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('creates a new seminar from the admin form', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $location = SeminarLocation::factory()->create([
        'name' => 'Auditório Principal',
        'max_vacancies' => 100,
    ]);
    SeminarType::factory()->create(['name' => 'Palestra']);
    $speaker = User::factory()->speaker()->create([
        'name' => 'Palestrante Convidado',
    ]);

    $page = visit('/admin/seminars/new');

    $page->assertSee('Novo Seminário')
        ->assertNoJavascriptErrors()
        // Basic fields
        ->fill('name', 'Computação Quântica Aplicada')
        ->fill('scheduled_at', now()->addDays(10)->format('Y-m-d\TH:i'))
        // Location (Radix Select — open trigger then pick the single rendered
        // option. The option label contains "(Cap: N)", whose parens break the
        // text-or-CSS click resolver, so target the listbox option by role.)
        ->click('Selecione um local')
        ->assertSee("{$location->name} (Cap: {$location->max_vacancies})")
        ->click('[role="option"]')
        // Subjects (tag input — type a topic into the tag field, then Enter
        // adds it. The input has no name/id, so target it by placeholder via a
        // CSS selector.)
        ->type('input[placeholder="Digite e pressione Enter..."]', 'Algoritmos Quânticos')
        ->keys('input[placeholder="Digite e pressione Enter..."]', 'Enter')
        ->assertSee('Algoritmos Quânticos')
        // Speakers (modal — open, search, check, confirm)
        ->click('Selecionar Palestrantes')
        ->assertSee('Selecionar Palestrantes')
        ->type('input[placeholder="Buscar usuários..."]', 'Palestrante Convidado')
        ->assertSee('Palestrante Convidado')
        // The row uses a Radix Checkbox (button[role=checkbox] + hidden proxy
        // input). Click the visible control so onCheckedChange fires.
        ->click('[role="dialog"] button[role="checkbox"]')
        // The confirm button label carries a live count "Confirmar (N)", whose
        // parens break the text resolver — match it by role/text instead.
        ->click('[role="dialog"] button:has-text("Confirmar")')
        ->assertSee('Palestrante Convidado')
        // Submit
        ->click('Criar Seminário')
        ->assertPathIs('/admin/seminars')
        ->assertSee('Computação Quântica Aplicada');

    expect(Seminar::where('name', 'Computação Quântica Aplicada')->exists())->toBeTrue();

    $seminar = Seminar::where('name', 'Computação Quântica Aplicada')->first();
    expect($seminar->seminar_location_id)->toBe($location->id)
        ->and($seminar->subjects()->pluck('name')->all())->toContain('Algoritmos Quânticos')
        ->and($seminar->speakers()->pluck('users.id')->all())->toContain($speaker->id);
});

it('edits an existing seminar', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $seminar = Seminar::factory()
        ->withSpeakers()
        ->withSubjects()
        ->create([
            'name' => 'Nome Antigo da Apresentação',
            'created_by' => $admin->id,
        ]);

    $page = visit("/admin/seminars/{$seminar->id}/edit");

    $page->assertSee('Editar Seminário')
        ->assertNoJavascriptErrors()
        ->assertValue('input[name="name"]', 'Nome Antigo da Apresentação')
        ->fill('name', 'Nome Atualizado da Apresentação')
        ->click('Atualizar Seminário')
        ->assertPathIs('/admin/seminars')
        ->assertSee('Nome Atualizado da Apresentação');

    expect($seminar->fresh()->name)->toBe('Nome Atualizado da Apresentação');
});

it('shows validation errors when required fields are empty', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $page = visit('/admin/seminars/new');

    $page->assertSee('Novo Seminário')
        ->click('Criar Seminário')
        ->assertPathIs('/admin/seminars/new')
        ->assertSee('Nome é obrigatório');

    expect(Seminar::count())->toBe(0);
});
