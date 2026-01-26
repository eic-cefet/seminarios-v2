<?php

use App\Mail\WelcomeUser;
use App\Models\User;

describe('WelcomeUser Mail', function () {
    it('has correct subject with app name', function () {
        $user = User::factory()->create();

        $mail = new WelcomeUser($user);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Bem-vindo(a) ao '.config('mail.name'));
    });

    it('uses markdown template', function () {
        $user = User::factory()->create();

        $mail = new WelcomeUser($user);
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.welcome');
    });

    it('passes user name to template', function () {
        $user = User::factory()->create([
            'name' => 'Maria Silva',
        ]);

        $mail = new WelcomeUser($user);
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Maria Silva');
    });

    it('passes login url to template', function () {
        $user = User::factory()->create();

        $mail = new WelcomeUser($user);
        $content = $mail->content();

        expect($content->with['loginUrl'])->toBe(url('/'));
    });

    it('stores user reference', function () {
        $user = User::factory()->create();

        $mail = new WelcomeUser($user);

        expect($mail->user->id)->toBe($user->id);
    });

    it('can be rendered', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
        ]);

        $mail = new WelcomeUser($user);

        expect($mail->render())->toBeString();
    });
});
