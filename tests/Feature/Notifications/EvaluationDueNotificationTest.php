<?php

use App\Models\Seminar;
use App\Models\SeminarType;
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
    $seminar = Seminar::factory()->create(['name' => 'DevOps 101', 'seminar_type_id' => null]);
    $notification = new EvaluationDueNotification($seminar);

    $data = $notification->toDatabase(User::factory()->make());

    expect($data['body'])->toBe('Avalie a apresentação "DevOps 101".');
});

it('names a masculine type with the definite article', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create(['name' => 'AI', 'seminar_type_id' => $type->id]);

    $body = (new EvaluationDueNotification($seminar))->toDatabase(User::factory()->make())['body'];

    expect($body)->toBe('Avalie o seminário "AI".');
});
