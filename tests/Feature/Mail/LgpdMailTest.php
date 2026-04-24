<?php

use App\Mail\AccountAnonymized;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionScheduled;
use App\Models\User;
use Illuminate\Support\Carbon;

describe('AccountAnonymized', function () {
    it('has correct subject', function () {
        $mail = new AccountAnonymized('Maria Silva');

        expect($mail->envelope()->subject)->toBe('Sua conta foi removida');
    });

    it('uses markdown template with user name', function () {
        $mail = new AccountAnonymized('Maria Silva');
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.account-anonymized')
            ->and($content->with['userName'])->toBe('Maria Silva');
    });

    it('includes anti-threading headers', function () {
        $mail = new AccountAnonymized('Maria Silva');
        $headers = $mail->headers();
        $text = $headers->text;

        expect($text['X-Mail-Class'])->toBe(AccountAnonymized::class)
            ->and($text)->toHaveKey('X-Entity-Ref-ID');
    });
});

describe('AccountDeletionCancelled', function () {
    it('has correct subject', function () {
        $user = User::factory()->create();
        $mail = new AccountDeletionCancelled($user);

        expect($mail->envelope()->subject)->toBe('Solicitação de exclusão de conta cancelada');
    });

    it('uses markdown template with user name', function () {
        $user = User::factory()->create(['name' => 'João Costa']);
        $mail = new AccountDeletionCancelled($user);
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.account-deletion-cancelled')
            ->and($content->with['userName'])->toBe('João Costa');
    });

    it('includes anti-threading headers with user id', function () {
        $user = User::factory()->create();
        $mail = new AccountDeletionCancelled($user);
        $headers = $mail->headers();
        $text = $headers->text;

        expect($text['X-Entity-Ref-ID'])->toBe('account-deletion-cancelled:'.$user->id)
            ->and($text['X-Mail-Class'])->toBe(AccountDeletionCancelled::class);
    });
});

describe('AccountDeletionScheduled', function () {
    it('has correct subject', function () {
        $user = User::factory()->create();
        $scheduledFor = Carbon::parse('2026-05-23');
        $mail = new AccountDeletionScheduled($user, $scheduledFor);

        expect($mail->envelope()->subject)->toBe('Solicitação de exclusão de conta recebida');
    });

    it('uses markdown template with user name and formatted date', function () {
        $user = User::factory()->create(['name' => 'Ana Pereira']);
        $scheduledFor = Carbon::parse('2026-05-23');
        $mail = new AccountDeletionScheduled($user, $scheduledFor);
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.account-deletion-scheduled')
            ->and($content->with['userName'])->toBe('Ana Pereira')
            ->and($content->with['scheduledFor'])->toBe('23/05/2026');
    });

    it('includes anti-threading headers with user id', function () {
        $user = User::factory()->create();
        $mail = new AccountDeletionScheduled($user, now()->addDays(30));
        $headers = $mail->headers();
        $text = $headers->text;

        expect($text['X-Entity-Ref-ID'])->toBe('account-deletion-scheduled:'.$user->id)
            ->and($text['X-Mail-Class'])->toBe(AccountDeletionScheduled::class);
    });
});
