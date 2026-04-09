<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

describe('ResetPassword Notification', function () {
    it('sends via mail channel', function () {
        $notification = new ResetPassword('test-token');
        $channels = $notification->via(new stdClass);

        expect($channels)->toBe(['mail']);
    });

    it('creates mail message with correct subject', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $notification = new ResetPassword('test-token-123');
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->subject)->toBe('Redefinir Senha - '.config('app.name'));
    });

    it('creates mail message with reset url containing token and email', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $notification = new ResetPassword('test-token-abc');
        $mailMessage = $notification->toMail($user);

        $actionUrl = $mailMessage->actionUrl;
        expect($actionUrl)->toContain('/redefinir-senha');
        expect($actionUrl)->toContain('token=test-token-abc');
        expect($actionUrl)->toContain('email='.urlencode('user@example.com'));
    });

    it('creates mail message with greeting', function () {
        $user = User::factory()->create();

        $notification = new ResetPassword('token');
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->greeting)->toBe('Olá!');
    });

    it('creates mail message with action button', function () {
        $user = User::factory()->create();

        $notification = new ResetPassword('token');
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->actionText)->toBe('Redefinir Senha');
    });

    it('stores token in notification', function () {
        $notification = new ResetPassword('my-secret-token');

        expect($notification->token)->toBe('my-secret-token');
    });

    it('can be queued', function () {
        Notification::fake();

        $user = User::factory()->create();
        $user->notify(new ResetPassword('queued-token'));

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('sends notification to user email', function () {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $user->notify(new ResetPassword('notification-token'));

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) {
                return $notification->token === 'notification-token';
            }
        );
    });
});
