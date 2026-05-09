<?php

use App\Mail\SpeakerSeminarReminder;
use App\Models\Seminar;
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

it('uses gender-neutral phrasing in body', function () {
    $speaker = User::factory()->create(['name' => 'Prof. Lima']);
    $seminar = Seminar::factory()->create(['name' => 'Algoritmos', 'scheduled_at' => now()->addDay()]);

    $mailable = new SpeakerSeminarReminder($speaker, $seminar);

    $mailable->assertSeeInHtml('amanhã você fará a apresentação abaixo');
    $mailable->assertDontSeeInHtml('apresentando o seminário');
});

it('skips ICS attachment when scheduled_at is null', function () {
    $speaker = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data-speaker']);
    $seminar->scheduled_at = null;

    $mailable = new SpeakerSeminarReminder($speaker, $seminar);

    expect($mailable->attachments())->toBe([]);
});
