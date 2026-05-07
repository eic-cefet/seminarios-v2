<?php

use App\Mail\SeminarReminder7d;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;

it('renders the 7-day reminder copy and subject', function () {
    $user = User::factory()->create(['name' => 'Bia']);
    $seminars = collect([Seminar::factory()->create([
        'name' => 'Compiladores',
        'scheduled_at' => now()->addDays(7),
        'seminar_type_id' => null,
    ])]);

    $mailable = new SeminarReminder7d($user, $seminars);

    $mailable->assertHasSubject('Lembrete: Seminário na próxima semana! - '.config('mail.name'));
    $mailable->assertSeeInHtml('Bia');
    $mailable->assertSeeInHtml('Compiladores');
    $mailable->assertSeeInHtml('na próxima semana');
});

it('uses plural subject for multiple seminars', function () {
    $user = User::factory()->create(['name' => 'Bia']);
    $seminars = collect([
        Seminar::factory()->create(['name' => 'A', 'scheduled_at' => now()->addDays(7), 'seminar_type_id' => null]),
        Seminar::factory()->create(['name' => 'B', 'scheduled_at' => now()->addDays(7), 'seminar_type_id' => null]),
    ]);

    $mailable = new SeminarReminder7d($user, $seminars);

    $mailable->assertHasSubject('Lembrete: 2 apresentações na próxima semana! - '.config('mail.name'));
});

it('skips ICS attachments for seminars without scheduled_at', function () {
    $user = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data-7d']);
    $seminar->scheduled_at = null;
    $seminars = collect([$seminar]);

    $mailable = new SeminarReminder7d($user, $seminars);

    expect($mailable->attachments())->toBe([]);
});

it('subject uses {Type} for a single seminar', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'X']);
    $user = User::factory()->create();

    $mail = new SeminarReminder7d($user, collect([$seminar]));

    expect($mail->envelope()->subject)->toBe('Lembrete: Dissertação na próxima semana! - '.config('mail.name'));
});

it('subject uses single-type plural when all seminars share one type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminars = Seminar::factory()->count(3)->for($type, 'seminarType')->create();
    $user = User::factory()->create();

    $mail = new SeminarReminder7d($user, $seminars);

    expect($mail->envelope()->subject)->toBe('Lembrete: 3 dissertações na próxima semana! - '.config('mail.name'));
});

it('subject falls back to "apresentações" for mixed types', function () {
    $masc = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $fem = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminars = collect([
        Seminar::factory()->for($masc, 'seminarType')->create(),
        Seminar::factory()->for($fem, 'seminarType')->create(),
    ])->each->load('seminarType');
    $user = User::factory()->create();

    $mail = new SeminarReminder7d($user, $seminars);

    expect($mail->envelope()->subject)->toBe('Lembrete: 2 apresentações na próxima semana! - '.config('mail.name'));
});

it('singular body uses the seminar type article and noun', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create();
    $user = User::factory()->create();

    $rendered = (new SeminarReminder7d($user, collect([$seminar])))->render();

    expect($rendered)
        ->toContain('Lembrete de Dissertação')
        ->toContain('Você está inscrito na dissertação que acontecerá')
        ->toContain('Ver Detalhes da Dissertação');
});

it('plural body for mixed types uses "apresentações" with feminine articles', function () {
    $masc = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $fem = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminars = collect([
        Seminar::factory()->for($masc, 'seminarType')->create(),
        Seminar::factory()->for($fem, 'seminarType')->create(),
    ])->each->load('seminarType');
    $user = User::factory()->create();

    $rendered = (new SeminarReminder7d($user, $seminars))->render();

    expect($rendered)
        ->toContain('Lembrete de Apresentações')
        ->toContain('Você está inscrito nas apresentações que acontecerão');
});

it('plural body for single shared type uses that type plural', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminars = Seminar::factory()->count(2)->for($type, 'seminarType')->create();
    $user = User::factory()->create();

    $rendered = (new SeminarReminder7d($user, $seminars))->render();

    expect($rendered)->toContain('Você está inscrito nas dissertações que acontecerão');
});
