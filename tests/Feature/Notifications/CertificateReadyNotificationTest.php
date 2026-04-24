<?php

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use Illuminate\Support\Facades\Notification;

it('records a database notification with seminar context and certificate URL', function () {
    Notification::fake();

    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['name' => 'AI in Education']);

    $user->notify(new CertificateReadyNotification($seminar, '/profile/certificates/42'));

    Notification::assertSentTo($user, CertificateReadyNotification::class, function ($notification) use ($seminar) {
        $data = $notification->toDatabase(User::factory()->make());

        return $data['category'] === 'certificate_ready'
            && str_contains($data['body'], $seminar->name)
            && $data['action_url'] === '/profile/certificates/42';
    });
});
