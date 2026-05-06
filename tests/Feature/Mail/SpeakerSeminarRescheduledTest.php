<?php

use App\Mail\SpeakerSeminarRescheduled;
use App\Models\Seminar;
use App\Models\SeminarType;
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

it('attaches an ICS calendar file when scheduled_at is set', function () {
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(5)]);
    $old = now()->addDays(2);

    $mailable = new SpeakerSeminarRescheduled($speaker, $seminar, $old);
    $attachments = $mailable->attachments();

    expect($attachments)->toHaveCount(1);
    expect($attachments[0]->as)->toContain('seminario-');
    expect(str_contains($attachments[0]->as, (string) $seminar->slug) || str_contains($attachments[0]->as, (string) $seminar->id))->toBeTrue();
    expect($attachments[0]->mime)->toBe('text/calendar');
});

it('skips ICS attachment when scheduled_at is null', function () {
    $speaker = User::factory()->create();
    $seminar = Seminar::factory()->create();
    $seminar->scheduled_at = null;
    $old = now()->addDays(2);

    $mailable = new SpeakerSeminarRescheduled($speaker, $seminar, $old);

    expect($mailable->attachments())->toBe([]);
});

it('keeps "Sua apresentação foi remarcada" wording regardless of seminar type gender', function () {
    foreach ([
        SeminarType::factory()->masculine()->create(['name' => 'Seminário', 'name_plural' => 'Seminários']),
        SeminarType::factory()->feminine()->create(['name' => 'Dissertação', 'name_plural' => 'Dissertações']),
    ] as $type) {
        $seminar = Seminar::factory()->for($type, 'seminarType')->create([
            'name' => 'Test',
            'scheduled_at' => now()->addDays(5),
        ]);
        $speaker = User::factory()->create();
        $previous = now()->addDays(2);

        $mailable = new SpeakerSeminarRescheduled($speaker, $seminar, $previous);

        $mailable->assertHasSubject('Sua apresentação foi remarcada: Test - '.config('mail.name'));
        $mailable->assertSeeInHtml('Sua apresentação foi remarcada');
        $mailable->assertSeeInHtml('A apresentação abaixo foi remarcada');
    }
});
