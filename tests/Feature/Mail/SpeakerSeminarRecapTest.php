<?php

use App\Mail\SpeakerSeminarRecap;
use App\Models\Seminar;
use App\Models\User;

it('renders the attendee count and seminar info', function () {
    $speaker = User::factory()->create(['name' => 'Prof. Lima']);
    $seminar = Seminar::factory()->create(['name' => 'Redes', 'scheduled_at' => now()->subDays(2)]);

    $mailable = new SpeakerSeminarRecap($speaker, $seminar, attendeesPresent: 24);

    $mailable->assertSeeInHtml('Prof. Lima');
    $mailable->assertSeeInHtml('Redes');
    $mailable->assertSeeInHtml('24 pessoas');
    $mailable->assertHasSubject('Resumo da sua apresentação: Redes - '.config('mail.name'));
    $mailable->assertSeeInHtml('Realizada em:');
    $mailable->assertSeeInHtml('Ver apresentação');
    $mailable->assertDontSeeInHtml('Realizado em:');
    $mailable->assertDontSeeInHtml('Ver seminário');
});

it('uses gender-neutral feminine "apresentação" in zero-attendance footer', function () {
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(2)]);

    $mailable = new SpeakerSeminarRecap($speaker, $seminar, attendeesPresent: 0);

    $mailable->assertSeeInHtml('Nenhuma presença foi marcada para esta apresentação');
    $mailable->assertSeeInHtml('pela página da apresentação');
    $mailable->assertDontSeeInHtml('para este seminário');
    $mailable->assertDontSeeInHtml('página do seminário');
});
