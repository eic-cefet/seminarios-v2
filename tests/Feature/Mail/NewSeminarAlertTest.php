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

it('uses gender-neutral feminine "apresentação" in subject and body', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'name' => 'Palestra sobre IA',
        'seminar_type_id' => null,
    ]);

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toStartWith('Nova apresentação: Palestra sobre IA');

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('Nova Apresentação Disponível')
        ->toContain('Uma nova apresentação combina com suas preferências de alerta.')
        ->toContain('Ver Detalhes da Apresentação')
        ->toContain('alertas de novas apresentações')
        ->not->toContain('Novo Seminário')
        ->not->toContain('novo seminário')
        ->not->toContain('Detalhes do Seminário')
        ->not->toContain('novos seminários');
});

it('agrees the heading and intro with a masculine type', function () {
    $user = User::factory()->create();
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create([
        'name' => 'AI',
        'seminar_type_id' => $type->id,
    ]);

    $mail = new NewSeminarAlert($user, $seminar);

    expect($mail->envelope()->subject)->toStartWith('Novo seminário: AI');

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('Novo Seminário Disponível')
        ->toContain('Um novo seminário combina')
        ->not->toContain('Nova Apresentação Disponível')
        ->not->toContain('Uma nova apresentação combina');
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
