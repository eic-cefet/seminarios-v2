<?php

use App\Mail\NewSeminarAlert;
use App\Models\Seminar;
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
