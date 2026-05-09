<?php

use App\Mail\SeminarReminder7d;
use App\Models\Seminar;
use App\Models\User;

it('renders the 7-day reminder copy and subject', function () {
    $user = User::factory()->create(['name' => 'Bia']);
    $seminars = collect([Seminar::factory()->create(['name' => 'Compiladores', 'scheduled_at' => now()->addDays(7)])]);

    $mailable = new SeminarReminder7d($user, $seminars);

    $mailable->assertHasSubject('Lembrete: Apresentação na próxima semana! - '.config('mail.name'));
    $mailable->assertSeeInHtml('Bia');
    $mailable->assertSeeInHtml('Compiladores');
    $mailable->assertSeeInHtml('na próxima semana');
    $mailable->assertSeeInHtml('Lembrete de Apresentação');
    $mailable->assertSeeInHtml('inscrito na apresentação');
    $mailable->assertSeeInHtml('Ver Detalhes da Apresentação');
    $mailable->assertDontSeeInHtml('Lembrete de Seminário');
    $mailable->assertDontSeeInHtml('inscrito no seminário');
    $mailable->assertDontSeeInHtml('Detalhes do Seminário');
});

it('uses plural subject for multiple seminars', function () {
    $user = User::factory()->create(['name' => 'Bia']);
    $seminars = collect([
        Seminar::factory()->create(['name' => 'A', 'scheduled_at' => now()->addDays(7)]),
        Seminar::factory()->create(['name' => 'B', 'scheduled_at' => now()->addDays(7)]),
    ]);

    $mailable = new SeminarReminder7d($user, $seminars);

    $mailable->assertHasSubject('Lembrete: 2 apresentações na próxima semana! - '.config('mail.name'));
    $mailable->assertSeeInHtml('Lembrete de Apresentações');
    $mailable->assertSeeInHtml('inscrito nas apresentações');
    $mailable->assertDontSeeInHtml('Lembrete de Seminários');
    $mailable->assertDontSeeInHtml('inscrito nos seminários');
});

it('skips ICS attachments for seminars without scheduled_at', function () {
    $user = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data-7d']);
    $seminar->scheduled_at = null;
    $seminars = collect([$seminar]);

    $mailable = new SeminarReminder7d($user, $seminars);

    expect($mailable->attachments())->toBe([]);
});
