<?php

use App\Mail\SpeakerSeminarRecap;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;

it('renders the attendee count and seminar info', function () {
    $speaker = User::factory()->create(['name' => 'Prof. Lima']);
    $seminar = Seminar::factory()->create(['name' => 'Redes', 'scheduled_at' => now()->subDays(2)]);

    $mailable = new SpeakerSeminarRecap($speaker, $seminar, attendeesPresent: 24);

    $mailable->assertSeeInHtml('Prof. Lima');
    $mailable->assertSeeInHtml('Redes');
    $mailable->assertSeeInHtml('24 pessoas');
    $mailable->assertHasSubject('Resumo da sua apresentação: Redes - '.config('mail.name'));
});

it('uses feminine demonstratives for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create([
        'name' => 'X',
        'scheduled_at' => now()->subDays(2),
    ]);
    $speaker = User::factory()->create();

    $rendered = (new SpeakerSeminarRecap($speaker, $seminar, 0))->render();

    expect($rendered)
        ->toContain('Nenhuma presença foi marcada para esta dissertação')
        ->toContain('pela página da dissertação')
        ->toContain('Ver dissertação');
});

it('keeps masculine demonstratives for a masculine type', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create([
        'name' => 'Y',
        'scheduled_at' => now()->subDays(2),
    ]);
    $speaker = User::factory()->create();

    $rendered = (new SpeakerSeminarRecap($speaker, $seminar, 0))->render();

    expect($rendered)
        ->toContain('Nenhuma presença foi marcada para este seminário')
        ->toContain('pela página do seminário')
        ->toContain('Ver seminário');
});

it('falls back to "este seminário" when type is null', function () {
    $seminar = Seminar::factory()->create([
        'name' => 'Z',
        'seminar_type_id' => null,
        'scheduled_at' => now()->subDays(2),
    ]);
    $speaker = User::factory()->create();

    $rendered = (new SpeakerSeminarRecap($speaker, $seminar, 0))->render();

    expect($rendered)
        ->toContain('Nenhuma presença foi marcada para este seminário')
        ->toContain('Ver seminário');
});
