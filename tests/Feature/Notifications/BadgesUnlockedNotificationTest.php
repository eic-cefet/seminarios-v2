<?php

use App\Models\User;
use App\Notifications\BadgesUnlockedNotification;
use App\Services\UserAnonymizationService;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

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

it('delivers database notifications to active users', function () {
    $user = User::factory()->create();

    $user->notifyNow(new BadgesUnlockedNotification([
        ['name' => 'Primeiro Passo'],
    ]));

    expect(DB::table('notifications')
        ->where('notifiable_type', User::class)
        ->where('notifiable_id', $user->id)
        ->where('type', BadgesUnlockedNotification::class)
        ->exists())->toBeTrue();
});

it('rejects missing soft-deleted and anonymized recipients at delivery time', function () {
    $notification = new BadgesUnlockedNotification([
        ['name' => 'Primeiro Passo'],
    ]);
    $missingUser = User::factory()->make();
    $softDeletedUser = User::factory()->create();
    $softDeletedUser->delete();
    $anonymizedUser = User::factory()->create(['anonymized_at' => now()]);

    expect($notification->shouldSend($missingUser, 'database'))->toBeFalse()
        ->and($notification->shouldSend($softDeletedUser, 'database'))->toBeFalse()
        ->and($notification->shouldSend($anonymizedUser, 'database'))->toBeFalse();
});

it('does not deliver a queued badge notification after the user is anonymized', function () {
    Queue::fake();
    $user = User::factory()->create();

    $user->notify(new BadgesUnlockedNotification([
        ['name' => 'Primeiro Passo'],
    ]));
    $queuedJob = Queue::pushed(SendQueuedNotifications::class)->sole();
    $serializedJob = serialize($queuedJob);

    app(UserAnonymizationService::class)->anonymize($user);

    unserialize($serializedJob)->handle(app(ChannelManager::class));

    expect(DB::table('notifications')
        ->where('notifiable_type', User::class)
        ->where('notifiable_id', $user->id)
        ->where('type', BadgesUnlockedNotification::class)
        ->exists())->toBeFalse();
});
