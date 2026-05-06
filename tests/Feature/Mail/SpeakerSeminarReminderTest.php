<?php

use App\Mail\SpeakerSeminarReminder;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;

it('renders speaker-targeted copy', function () {
    $speaker = User::factory()->create(['name' => 'Prof. Lima']);
    $seminar = Seminar::factory()->create(['name' => 'Algoritmos', 'scheduled_at' => now()->addDay()]);

    $mailable = new SpeakerSeminarReminder($speaker, $seminar);

    $mailable->assertSeeInHtml('Prof. Lima');
    $mailable->assertSeeInHtml('Algoritmos');
    $mailable->assertSeeInHtml('você está apresentando');
    $mailable->assertHasSubject('Você apresenta amanhã: Algoritmos - '.config('mail.name'));
});

it('skips ICS attachment when scheduled_at is null', function () {
    $speaker = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data-speaker']);
    $seminar->scheduled_at = null;

    $mailable = new SpeakerSeminarReminder($speaker, $seminar);

    expect($mailable->attachments())->toBe([]);
});

it('renders feminine article in the body for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'X']);
    $user = User::factory()->create();

    $rendered = (new SpeakerSeminarReminder($user, $seminar))->render();

    expect($rendered)->toContain('amanhã você está apresentando a dissertação abaixo');
});

it('keeps masculine wording for a masculine type', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'Y']);
    $user = User::factory()->create();

    $rendered = (new SpeakerSeminarReminder($user, $seminar))->render();

    expect($rendered)->toContain('amanhã você está apresentando o seminário abaixo');
});

it('falls back to "o seminário" when seminar has no type', function () {
    $seminar = Seminar::factory()->create(['name' => 'Z', 'seminar_type_id' => null]);
    $user = User::factory()->create();

    $rendered = (new SpeakerSeminarReminder($user, $seminar))->render();

    expect($rendered)->toContain('amanhã você está apresentando o seminário abaixo');
});
