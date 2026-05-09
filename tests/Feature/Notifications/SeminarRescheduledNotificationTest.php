<?php

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\SeminarRescheduledNotification;
use Illuminate\Support\Facades\Notification;

it('records a notification with seminar context', function () {
    Notification::fake();

    $user = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'name' => 'Quantum Computing',
        'scheduled_at' => '2026-05-10 14:00:00',
    ]);

    $user->notify(new SeminarRescheduledNotification($seminar, previousStartsAt: '2026-05-05 14:00:00'));

    Notification::assertSentTo($user, SeminarRescheduledNotification::class, function ($notification) use ($seminar) {
        $data = $notification->toDatabase(User::factory()->make());

        return $data['category'] === 'seminar_rescheduled'
            && str_contains($data['body'], $seminar->name);
    });
});

it('uses gender-neutral feminine title and body', function () {
    $seminar = Seminar::factory()->create(['name' => 'Quantum Computing']);
    $notification = new SeminarRescheduledNotification($seminar, previousStartsAt: '2026-05-05 14:00:00');

    $data = $notification->toDatabase(User::factory()->make());

    expect($data['title'])->toBe('Apresentação reagendada');
    expect($data['body'])->toBe('"Quantum Computing" foi reagendada. Confira a nova data.');
});
