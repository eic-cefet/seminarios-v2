<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('walks a user through requesting and completing a password reset', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => bcrypt('old-password'),
    ]);

    $page = visit('/recuperar-senha');

    $page->fill('email', 'reset@example.com')
        ->click('button[type="submit"]')
        ->assertSee('E-mail enviado!');

    $token = null;

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    expect($token)->not->toBeNull();

    $page = visit("/redefinir-senha?token={$token}&email=reset%40example.com");

    $page->fill('password', 'new-secret-456')
        ->fill('passwordConfirmation', 'new-secret-456')
        ->click('button[type="submit"]')
        ->assertSee('Senha redefinida!');

    expect(Hash::check('new-secret-456', $user->fresh()->password))->toBeTrue();
});

it('rejects mismatched password confirmation on the reset page', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    $page = visit("/redefinir-senha?token={$token}&email=reset%40example.com");

    $page->fill('password', 'new-secret-456')
        ->fill('passwordConfirmation', 'different-789')
        ->click('button[type="submit"]')
        ->assertPathIs('/redefinir-senha')
        ->assertSee('não coincidem');
});
