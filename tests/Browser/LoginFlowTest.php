<?php

use App\Models\User;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('logs a user in with valid credentials and lands on the home seminars view', function () {
    User::factory()->create([
        'name' => 'Ana Souza',
        'email' => 'ana@example.com',
        'password' => bcrypt('secret-pass-123'),
    ]);

    $page = visit('/login');

    $page->assertSee('Entrar')
        ->fill('email', 'ana@example.com')
        ->fill('password', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->assertSee('Ana');
});

it('shows an error and stays on /login when credentials are invalid', function () {
    User::factory()->create([
        'email' => 'ana@example.com',
        'password' => bcrypt('secret-pass-123'),
    ]);

    $page = visit('/login');

    $page->fill('email', 'ana@example.com')
        ->fill('password', 'wrong-password')
        ->click('button[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee('incorretos');
});
