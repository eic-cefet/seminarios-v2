<?php

use App\Mail\SpeakerSeminarRescheduled;
use App\Models\Seminar;
use App\Models\User;

it('renders speaker reschedule subject and old/new dates', function () {
    $speaker = User::factory()->create(['name' => 'Prof. Lima']);
    $seminar = Seminar::factory()->create(['name' => 'Banco de Dados', 'scheduled_at' => now()->addDays(5)->setTime(14, 0)]);
    $old = now()->addDays(2)->setTime(14, 0);

    $mailable = new SpeakerSeminarRescheduled($speaker, $seminar, $old);

    $mailable->assertHasSubject('Sua apresentação foi remarcada: Banco de Dados - '.config('mail.name'));
    $mailable->assertSeeInHtml('Prof. Lima');
    $mailable->assertSeeInHtml($old->format('d/m/Y'));
    $mailable->assertSeeInHtml($seminar->scheduled_at->format('d/m/Y'));
});
