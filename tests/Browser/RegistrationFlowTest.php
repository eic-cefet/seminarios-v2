<?php

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('blocks submission when consent checkboxes are unchecked', function () {
    Course::factory()->create(['name' => 'Sistemas de Informação']);

    $page = visit('/cadastro');

    $page->fill('name', 'João Silva')
        ->fill('email', 'joao@example.com')
        ->select('courseSituation', CourseSituation::Studying->value)
        ->select('courseRole', CourseRole::Aluno->value)
        ->fill('password', 'secret-pass-123')
        ->fill('passwordConfirmation', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->assertPathIs('/cadastro')
        ->assertSee('Termos de Uso');

    expect(User::where('email', 'joao@example.com')->exists())->toBeFalse();
});

it('rejects duplicate emails server-side', function () {
    Course::factory()->create(['name' => 'Sistemas de Informação']);
    User::factory()->create(['email' => 'taken@example.com']);

    $page = visit('/cadastro');

    $page->fill('name', 'João Silva')
        ->fill('email', 'taken@example.com')
        ->select('courseSituation', CourseSituation::Studying->value)
        ->select('courseRole', CourseRole::Aluno->value)
        ->fill('password', 'secret-pass-123')
        ->fill('passwordConfirmation', 'secret-pass-123')
        ->check('acceptedTerms')
        ->check('acceptedPrivacy')
        ->click('button[type="submit"]')
        ->assertPathIs('/cadastro')
        ->assertSee('Verifique os dados informados');

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('creates a user and signs them in on a fully valid submission', function () {
    Course::factory()->create(['name' => 'Sistemas de Informação']);

    $page = visit('/cadastro');

    $page->fill('name', 'João Silva')
        ->fill('email', 'joao@example.com')
        ->select('courseSituation', CourseSituation::Studying->value)
        ->select('courseRole', CourseRole::Aluno->value)
        ->fill('password', 'secret-pass-123')
        ->fill('passwordConfirmation', 'secret-pass-123')
        ->check('acceptedTerms')
        ->check('acceptedPrivacy')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->assertSee('João');

    expect(User::where('email', 'joao@example.com')->exists())->toBeTrue();
});
