<?php

use App\Mail\SeminarRegistrationConfirmation;
use App\Models\Seminar;
use App\Models\SeminarType;
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

it('uses gender-neutral feminine "apresentação" in body', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $seminar = Seminar::factory()->create(['name' => 'IA na Prática', 'seminar_type_id' => null]);

    $mailable = new SeminarRegistrationConfirmation($user, $seminar);

    $mailable->assertSeeInHtml('Sua inscrição na apresentação abaixo foi confirmada');
    $mailable->assertSeeInHtml('Ver detalhes da apresentação');
    $mailable->assertSeeInHtml('cancele sua inscrição na página da apresentação');
    $mailable->assertDontSeeInHtml('inscrição no seminário');
    $mailable->assertDontSeeInHtml('Ver detalhes do seminário');
    $mailable->assertDontSeeInHtml('página do seminário');
});

it('names a masculine presentation type with the em-contraction', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create(['seminar_type_id' => $type->id]);
    $user = User::factory()->create();

    $rendered = (new SeminarRegistrationConfirmation($user, $seminar))->render();

    expect($rendered)->toContain('Sua inscrição no seminário abaixo foi confirmada');
});

it('skips ICS attachment when scheduled_at is null', function () {
    $user = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data']);
    $seminar->scheduled_at = null;

    $mailable = new SeminarRegistrationConfirmation($user, $seminar);

    expect($mailable->attachments())->toBe([]);
});
