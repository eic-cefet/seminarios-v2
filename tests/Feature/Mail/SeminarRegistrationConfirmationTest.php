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

it('skips ICS attachment when scheduled_at is null', function () {
    $user = User::factory()->create();
    $seminar = new Seminar(['name' => 'Sem data', 'slug' => 'sem-data']);
    $seminar->scheduled_at = null;

    $mailable = new SeminarRegistrationConfirmation($user, $seminar);

    expect($mailable->attachments())->toBe([]);
});

it('uses feminine articles in the body for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'Método X']);
    $user = User::factory()->create(['name' => 'Ana']);

    $rendered = (new SeminarRegistrationConfirmation($user, $seminar))->render();

    expect($rendered)
        ->toContain('Sua inscrição na dissertação abaixo foi confirmada')
        ->toContain('Ver detalhes da dissertação')
        ->toContain('cancele sua inscrição na página da dissertação');
});

it('keeps masculine articles in the body for a masculine type', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'X']);
    $user = User::factory()->create();

    $rendered = (new SeminarRegistrationConfirmation($user, $seminar))->render();

    expect($rendered)
        ->toContain('Sua inscrição no seminário abaixo foi confirmada')
        ->toContain('Ver detalhes do seminário')
        ->toContain('cancele sua inscrição na página do seminário');
});

it('falls back to "seminário" when seminar has no type', function () {
    $seminar = Seminar::factory()->create(['name' => 'X', 'seminar_type_id' => null]);
    $user = User::factory()->create();

    $rendered = (new SeminarRegistrationConfirmation($user, $seminar))->render();

    expect($rendered)->toContain('Sua inscrição no seminário abaixo foi confirmada');
});
