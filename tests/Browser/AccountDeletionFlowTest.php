<?php

use App\Mail\AccountDeletionConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped(
            'Frontend assets not built. Run `pnpm run build` (or rely on CI, which builds them automatically) before running browser tests locally.'
        );
    }
});

it('walks a user through requesting and confirming account deletion', function () {
    Mail::fake();

    $user = User::factory()->create([
        'name' => 'Carlos Mendes',
        'email' => 'delete@example.com',
        'password' => bcrypt('secret-pass-123'),
    ]);

    $this->actingAs($user);

    $page = visit('/perfil');

    $page->assertSee('Excluir minha conta')
        ->click('button:has-text("Excluir minha conta")')
        ->fill('[role="dialog"] input[type="password"]', 'secret-pass-123')
        ->click('button:has-text("Confirmar exclusão")')
        ->assertSee('Enviamos um link de confirmação');

    $token = null;
    Mail::assertQueued(
        AccountDeletionConfirmation::class,
        function ($mail) use ($user, &$token) {
            if (! $mail->hasTo($user->email)) {
                return false;
            }
            if (preg_match('~/confirmar-exclusao/([^/?#]+)~', $mail->confirmUrl, $m)) {
                $token = $m[1];
            }

            return true;
        },
    );

    expect($token)->not->toBeNull();

    $page = visit("/confirmar-exclusao/{$token}");

    $page->assertSee('Exclusão agendada')
        ->assertSee('Sua conta será excluída');

    expect($user->fresh()->anonymization_requested_at)->not->toBeNull();
});

it('rejects deletion request with a wrong password', function () {
    $user = User::factory()->create([
        'name' => 'Carlos Mendes',
        'password' => bcrypt('secret-pass-123'),
    ]);
    $this->actingAs($user);

    $page = visit('/perfil');

    $page->click('button:has-text("Excluir minha conta")')
        ->fill('[role="dialog"] input[type="password"]', 'wrong-password')
        ->click('button:has-text("Confirmar exclusão")')
        ->assertSee('incorretos');

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
});

it('rejects an expired or invalid confirmation token', function () {
    $user = User::factory()->create([
        'name' => 'Carlos Mendes',
    ]);
    $this->actingAs($user);

    $page = visit('/confirmar-exclusao/'.str_repeat('a', 64));

    $page->assertSee('Não foi possível confirmar');
});
