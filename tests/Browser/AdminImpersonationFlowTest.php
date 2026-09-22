<?php

use App\Models\User;

beforeEach(function () {
    config(['sanctum.stateful' => ['127.0.0.1:*', 'localhost:*']]);
    if (! file_exists(public_path('build/manifest.json')) && ! file_exists(public_path('hot'))) {
        $this->markTestSkipped('Frontend assets require a running dev server or the CI build.');
    }
});

it('lets an administrator access a selected user and return through the banner', function () {
    User::factory()->admin()->create([
        'name' => 'Admin Teste',
        'email' => 'admin@example.com',
        'password' => 'secret-pass-123',
    ]);
    User::factory()->student()->create(['name' => 'Maria Silva']);

    $page = visit('/login');
    $page->fill('email', 'admin@example.com')
        ->fill('password', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->assertPathIs('/');

    $page->navigate('/admin/users')
        ->click('button[aria-label="Ações de Maria Silva"]')
        ->assertSee('Editar')
        ->assertSee('Excluir')
        ->screenshot(false, 'user-actions-menu')
        ->click('Acessar como usuário')
        ->assertSee('Acessar como Maria Silva?')
        ->click('button:has-text("Acessar como usuário")')
        ->assertPathIs('/')
        ->assertSee('Acessando como')
        ->assertSee('Maria Silva')
        ->screenshot(false, 'impersonation-banner')
        ->assertNoJavascriptErrors()
        ->resize(390, 844)
        ->assertSee('Voltar ao administrador')
        ->screenshot(false, 'impersonation-banner-mobile')
        ->click('Voltar ao administrador')
        ->assertPathIs('/admin/users')
        ->assertSee('Lista de Usuarios')
        ->assertDontSee('Acessando como')
        ->assertNoJavascriptErrors();
});
