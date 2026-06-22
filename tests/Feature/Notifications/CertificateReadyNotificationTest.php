<?php

use App\Models\Seminar;
use App\Models\SeminarType;
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

it('uses gender-neutral feminine "apresentação" in body', function () {
    $seminar = Seminar::factory()->create(['name' => 'AI in Education', 'seminar_type_id' => null]);
    $notification = new CertificateReadyNotification($seminar, '/profile/certificates/42');

    $data = $notification->toDatabase(User::factory()->make());

    expect($data['body'])->toBe('O certificado da apresentação "AI in Education" já está disponível.');
});

it('names a masculine type with the de-contraction', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create(['name' => 'AI', 'seminar_type_id' => $type->id]);

    $body = (new CertificateReadyNotification($seminar, '/x'))->toDatabase(User::factory()->make())['body'];

    expect($body)->toBe('O certificado do seminário "AI" já está disponível.');
});
