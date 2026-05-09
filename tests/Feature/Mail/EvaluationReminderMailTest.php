<?php

use App\Mail\EvaluationReminder;
use App\Models\Seminar;
use App\Models\User;

describe('EvaluationReminder Mail', function () {
    it('has singular subject for one seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie a apresentação que você participou - '.config('mail.name'));
    });

    it('has plural subject for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(3)->create();

        $mail = new EvaluationReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie as 3 apresentações que você participou - '.config('mail.name'));
    });

    it('has plural subject for two seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new EvaluationReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie as 2 apresentações que você participou - '.config('mail.name'));
    });

    it('uses gender-neutral feminine "apresentação" in singular body', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $rendered = (new EvaluationReminder($user, collect([$seminar])))->render();

        expect($rendered)
            ->toContain('Você participou da apresentação abaixo')
            ->toContain('Avaliar Apresentação')
            ->toContain('após a realização da apresentação')
            ->not->toContain('Você participou do seminário')
            ->not->toContain('Avaliar Seminário')
            ->not->toContain('após a realização do seminário');
    });

    it('uses feminine plural "apresentações" in plural body', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $rendered = (new EvaluationReminder($user, $seminars))->render();

        expect($rendered)
            ->toContain('Você participou das apresentações abaixo')
            ->toContain('Avaliar Apresentações')
            ->not->toContain('Você participou dos seminários')
            ->not->toContain('Avaliar Seminários');
    });

    it('uses markdown template', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.evaluation-reminder');
    });

    it('passes user name to template', function () {
        $user = User::factory()->create([
            'name' => 'Ana Paula',
        ]);
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Ana Paula');
    });

    it('passes seminars collection to template', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new EvaluationReminder($user, $seminars);
        $content = $mail->content();

        expect($content->with['seminars'])->toHaveCount(2);
    });

    it('passes evaluation url to template', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->with['evaluationUrl'])->toBe(url('/avaliar'));
    });

    it('stores user reference', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));

        expect($mail->user->id)->toBe($user->id);
    });

    it('stores seminars collection', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new EvaluationReminder($user, $seminars);

        expect($mail->seminars)->toHaveCount(2);
    });
});
