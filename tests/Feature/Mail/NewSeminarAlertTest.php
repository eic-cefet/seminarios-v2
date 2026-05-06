<?php

use App\Mail\NewSeminarAlert;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sets subject to the seminar name', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['name' => 'Palestra sobre IA']);

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toContain('Palestra sobre IA');
});

it('includes the audit headers', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $mail = new NewSeminarAlert($user, $seminar);
    $headers = $mail->headers()->text;

    expect($headers)
        ->toHaveKey('X-Entity-Ref-ID')
        ->toHaveKey('X-Mail-Class', NewSeminarAlert::class);

    expect($headers['X-Entity-Ref-ID'])->toBe("seminar-alert:{$seminar->id}:{$user->id}");
});

it('renders the blade template with seminar context', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $seminar = Seminar::factory()->create(['name' => 'Palestra Kubernetes']);

    $rendered = (new NewSeminarAlert($user, $seminar))->render();

    expect($rendered)->toContain('Ana')->toContain('Palestra Kubernetes');
});

it('strips markdown syntax from the description excerpt', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'description' => "# Resumo\nFala sobre **redes** de passe no futebol.",
    ]);

    $rendered = (new NewSeminarAlert($user, $seminar))->render();

    expect($rendered)
        ->toContain('Resumo Fala sobre redes de passe no futebol.')
        ->not->toContain('# Resumo')
        ->not->toContain('**redes**');
});

it('uses "Novo {type}" in the subject for a masculine type', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')
        ->create(['name' => 'IA na Prática']);
    $user = User::factory()->create();

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toBe('Novo seminário: IA na Prática - '.config('mail.name'));
});

it('uses "Nova {type}" in the subject for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')
        ->create(['name' => 'Método para Avaliação']);
    $user = User::factory()->create();

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toBe('Nova dissertação: Método para Avaliação - '.config('mail.name'));
});

it('preserves acronym casing in the subject', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'TCC',
        'name_plural' => 'TCCs',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'Sistema X']);
    $user = User::factory()->create();

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toBe('Novo TCC: Sistema X - '.config('mail.name'));
});

it('falls back to "Novo seminário" when seminar has no type', function () {
    $seminar = Seminar::factory()->create([
        'name' => 'Sem tipo',
        'seminar_type_id' => null,
    ]);
    $user = User::factory()->create();

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toBe('Novo seminário: Sem tipo - '.config('mail.name'));
});

it('renders the body title and CTA with feminine wording for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'X']);
    $user = User::factory()->create(['name' => 'Ana']);

    $rendered = (new NewSeminarAlert($user, $seminar))->render();

    expect($rendered)
        ->toContain('Nova Dissertação Disponível')
        ->toContain('Ver Detalhes da Dissertação');
});

it('renders body intro with feminine "Uma nova dissertação" for a feminine type', function () {
    $type = SeminarType::factory()->feminine()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
    ]);
    $seminar = Seminar::factory()->for($type, 'seminarType')->create();
    $user = User::factory()->create();

    $rendered = (new NewSeminarAlert($user, $seminar))->render();

    expect($rendered)->toContain('Uma nova dissertação combina com suas preferências');
});
