<?php

use App\Mail\EvaluationReminder;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;

describe('EvaluationReminder Mail', function () {
    it('has singular subject for one seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['seminar_type_id' => null]);

        $mail = new EvaluationReminder($user, collect([$seminar]));
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie o seminário que você participou - '.config('mail.name'));
    });

    it('has plural subject for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(3)->create(['seminar_type_id' => null]);

        $mail = new EvaluationReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie as 3 apresentações que você participou - '.config('mail.name'));
    });

    it('has plural subject for two seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create(['seminar_type_id' => null]);

        $mail = new EvaluationReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Avalie as 2 apresentações que você participou - '.config('mail.name'));
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

    it('singular subject uses the seminar type', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        $mail = new EvaluationReminder(User::factory()->create(), collect([$seminar]));

        expect($mail->envelope()->subject)->toBe('Avalie a dissertação que você participou - '.config('mail.name'));
    });

    it('plural subject for mixed types uses "apresentações" with feminine article', function () {
        $masc = SeminarType::factory()->masculine()->create(['name' => 'Seminário', 'name_plural' => 'Seminários']);
        $fem = SeminarType::factory()->feminine()->create(['name' => 'Dissertação', 'name_plural' => 'Dissertações']);
        $seminars = collect([
            Seminar::factory()->for($masc, 'seminarType')->create(),
            Seminar::factory()->for($fem, 'seminarType')->create(),
        ]);

        $mail = new EvaluationReminder(User::factory()->create(), $seminars);

        expect($mail->envelope()->subject)->toBe('Avalie as 2 apresentações que você participou - '.config('mail.name'));
    });

    it('plural subject for single shared type uses that type plural', function () {
        $type = SeminarType::factory()->feminine()->create(['name' => 'Dissertação', 'name_plural' => 'Dissertações']);
        $seminars = Seminar::factory()->count(3)->for($type, 'seminarType')->create();

        $mail = new EvaluationReminder(User::factory()->create(), $seminars);

        expect($mail->envelope()->subject)->toBe('Avalie as 3 dissertações que você participou - '.config('mail.name'));
    });

    it('singular body uses the seminar type', function () {
        $type = SeminarType::factory()->feminine()->create(['name' => 'Dissertação', 'name_plural' => 'Dissertações']);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        $rendered = (new EvaluationReminder(User::factory()->create(), collect([$seminar])))->render();

        expect($rendered)
            ->toContain('Você participou da dissertação abaixo')
            ->toContain('Avaliar Dissertação')
            ->toContain('Você tem até 30 dias após a realização da dissertação para enviar sua avaliação');
    });

    it('plural body for mixed types uses "apresentações"', function () {
        $masc = SeminarType::factory()->masculine()->create(['name' => 'Seminário', 'name_plural' => 'Seminários']);
        $fem = SeminarType::factory()->feminine()->create(['name' => 'Dissertação', 'name_plural' => 'Dissertações']);
        $seminars = collect([
            Seminar::factory()->for($masc, 'seminarType')->create(),
            Seminar::factory()->for($fem, 'seminarType')->create(),
        ]);

        $rendered = (new EvaluationReminder(User::factory()->create(), $seminars))->render();

        expect($rendered)
            ->toContain('Você participou das apresentações abaixo')
            ->toContain('Avaliar Apresentações');
    });
});
