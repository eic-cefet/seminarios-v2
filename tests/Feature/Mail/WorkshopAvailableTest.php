<?php

use App\Mail\WorkshopAvailable;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders subject and CTA', function () {
    $user = User::factory()->create(['name' => 'Joana']);
    $workshop = Workshop::factory()->create(['name' => 'Engenharia de Prompts']);

    $mailable = new WorkshopAvailable($user, $workshop);

    $mailable->assertHasSubject('Novo workshop disponível: Engenharia de Prompts - '.config('mail.name'));
    $mailable->assertSeeInHtml('Joana');
    $mailable->assertSeeInHtml('Engenharia de Prompts');
});

it('includes the audit headers', function () {
    $user = User::factory()->create();
    $workshop = Workshop::factory()->create();

    $mail = new WorkshopAvailable($user, $workshop);
    $headers = $mail->headers()->text;

    expect($headers)
        ->toHaveKey('X-Entity-Ref-ID')
        ->toHaveKey('X-Mail-Class', WorkshopAvailable::class);

    expect($headers['X-Entity-Ref-ID'])->toBe("workshop-available:{$workshop->id}:{$user->id}");
});
