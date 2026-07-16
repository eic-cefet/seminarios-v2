<?php

use App\Models\User;
use App\Notifications\BadgesUnlockedNotification;

it('serializes a singular badge unlock notification', function () {
    $notification = new BadgesUnlockedNotification([
        ['name' => 'Primeiro Passo'],
    ]);

    $payload = $notification->toDatabase(User::factory()->make());

    expect($payload)->toMatchArray([
        'category' => 'gamification',
        'title' => 'Nova conquista desbloqueada!',
        'action_url' => '/conquistas',
    ])->and($payload['body'])->toContain('Primeiro Passo');
});

it('serializes a grouped notification with at most three badge names and the remaining count', function () {
    $notification = new BadgesUnlockedNotification([
        ['name' => 'Primeiro Passo'],
        ['name' => 'Pegando Ritmo'],
        ['name' => 'Dose Dupla'],
        ['name' => 'Semana Intensiva'],
        ['name' => 'Mente Curiosa'],
    ]);

    $payload = $notification->toDatabase(User::factory()->make());

    expect($payload['title'])->toBe('Novas conquistas desbloqueadas!')
        ->and($payload['body'])->toContain('5')
        ->toContain('Primeiro Passo')
        ->toContain('Pegando Ritmo')
        ->toContain('Dose Dupla')
        ->toContain('mais 2')
        ->not->toContain('Semana Intensiva')
        ->not->toContain('Mente Curiosa')
        ->and($payload['action_url'])->toBe('/conquistas');
});
