<?php

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\EvaluationDueNotification;
use Illuminate\Support\Facades\Notification;

it('records a notification pointing at the evaluation route', function () {
    Notification::fake();

    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['name' => 'DevOps 101', 'slug' => 'devops-101']);

    $user->notify(new EvaluationDueNotification($seminar));

    Notification::assertSentTo($user, EvaluationDueNotification::class, function ($notification) {
        $data = $notification->toDatabase(User::factory()->make());

        return $data['category'] === 'evaluation_due'
            && $data['action_url'] === '/avaliar';
    });
});

it('uses gender-neutral feminine "apresentação" in body', function () {
    $seminar = Seminar::factory()->create(['name' => 'DevOps 101']);
    $notification = new EvaluationDueNotification($seminar);

    $data = $notification->toDatabase(User::factory()->make());

    expect($data['body'])->toBe('Avalie a apresentação "DevOps 101".');
});
