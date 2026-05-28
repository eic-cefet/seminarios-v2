<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('redirects a 2FA-enabled user to the challenge page after valid credentials', function () {
    User::factory()->withTwoFactor()->create([
        'email' => 'mfa@example.com',
        'password' => bcrypt('secret-pass-123'),
    ]);

    $page = visit('/login');

    $page->fill('email', 'mfa@example.com')
        ->fill('password', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->assertPathIs('/login/two-factor')
        ->assertSee('código');
});

it('rejects an invalid 2FA code and stays on the challenge page', function () {
    User::factory()->withTwoFactor()->create([
        'email' => 'mfa@example.com',
        'password' => bcrypt('secret-pass-123'),
    ]);

    $page = visit('/login');

    $page->fill('email', 'mfa@example.com')
        ->fill('password', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->assertPathIs('/login/two-factor')
        ->fill('code', '000000')
        ->click('button[type="submit"]')
        ->assertPathIs('/login/two-factor')
        ->assertSee('Verifique os dados informados');
});

it('accepts a valid TOTP code and completes login', function () {
    Carbon::setTestNow(Carbon::now());

    $user = User::factory()->withTwoFactor()->create([
        'email' => 'mfa@example.com',
        'password' => bcrypt('secret-pass-123'),
        'name' => 'Maria Lopes',
    ]);

    $validCode = app(Google2FA::class)
        ->getCurrentOtp(decrypt($user->two_factor_secret));

    $page = visit('/login');

    $page->fill('email', 'mfa@example.com')
        ->fill('password', 'secret-pass-123')
        ->click('button[type="submit"]')
        ->fill('code', $validCode)
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->assertSee('Maria');
});
