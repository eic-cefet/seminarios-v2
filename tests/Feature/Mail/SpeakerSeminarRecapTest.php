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
});
