<?php

use App\Mail\SeminarRegistrationConfirmation;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('renders subject, addressee and seminar info', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $seminar = Seminar::factory()->create(['name' => 'IA na Prática']);

    $mailable = new SeminarRegistrationConfirmation($user, $seminar);

    $mailable->assertSeeInHtml('Ana');
    $mailable->assertSeeInHtml('IA na Prática');
    $mailable->assertHasSubject('Inscrição confirmada: IA na Prática - '.config('mail.name'));
});

it('skips ICS attachment when scheduled_at is null', function () {
    $user = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data']);
    $seminar->scheduled_at = null;

    $mailable = new SeminarRegistrationConfirmation($user, $seminar);

    expect($mailable->attachments())->toBe([]);
});
