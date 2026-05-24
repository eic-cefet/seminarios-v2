<?php

use App\Exceptions\ApiException;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\User;
use App\Services\RegistrationService;

beforeEach(fn () => $this->service = app(RegistrationService::class));

it('registers a user for an upcoming seminar', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);

    $reg = $this->service->register($user, $seminar);

    expect($reg->user_id)->toBe($user->id)->and($reg->seminar_id)->toBe($seminar->id);
});

it('refuses registration on a past seminar', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(1)]);

    expect(fn () => $this->service->register($user, $seminar))->toThrow(ApiException::class);
});

it('refuses double registration', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);

    $this->service->register($user, $seminar);
    expect(fn () => $this->service->register($user, $seminar))->toThrow(ApiException::class);
});

it('cancels registration when allowed', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
    $this->service->register($user, $seminar);

    $this->service->unregister($user, $seminar);

    expect($seminar->registrations()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('refuses cancellation on the day of or after a seminar', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()]);
    Registration::factory()->for($user)->for($seminar)->create();

    expect(fn () => $this->service->unregister($user, $seminar))->toThrow(ApiException::class);
});

it('refuses cancellation when user is not registered', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);

    expect(fn () => $this->service->unregister($user, $seminar))->toThrow(ApiException::class);
});

it('throws seminarFull when the seminar location is at capacity', function () {
    $location = SeminarLocation::factory()->create(['max_vacancies' => 1]);
    $seminar = Seminar::factory()->create([
        'seminar_location_id' => $location->id,
        'scheduled_at' => now()->addDays(7),
    ]);
    Registration::factory()->for($seminar)->create();

    $newUser = User::factory()->create();

    expect(fn () => $this->service->register($newUser, $seminar))
        ->toThrow(
            fn (ApiException $e) => expect($e->errorCode)->toBe('seminar_full')
        );
});

it('allows registration when the seminar location still has vacancies', function () {
    $location = SeminarLocation::factory()->create(['max_vacancies' => 100]);
    $seminar = Seminar::factory()->create([
        'seminar_location_id' => $location->id,
        'scheduled_at' => now()->addDays(7),
    ]);

    $newUser = User::factory()->create();

    $registration = $this->service->register($newUser, $seminar);

    expect($registration->user_id)->toBe($newUser->id)
        ->and($registration->seminar_id)->toBe($seminar->id);
});
