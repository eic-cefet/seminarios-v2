<?php

use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Database\Eloquent\Factories\Sequence;

beforeEach(fn () => $this->service = new RatingService);

it('exposes the evaluation window', function () {
    expect($this->service->windowStart()->isSameDay(now()->subDays(30)->startOfDay()))->toBeTrue();
});

it('says a recent past seminar is within the window', function () {
    expect($this->service->isWithinWindow(
        Seminar::factory()->create(['scheduled_at' => now()->subDays(5)])
    ))->toBeTrue();
});

it('says an old seminar is outside the window', function () {
    expect($this->service->isWithinWindow(
        Seminar::factory()->create(['scheduled_at' => now()->subDays(60)])
    ))->toBeFalse();
});

it('returns user pending evaluations for present registrations within window', function () {
    $user = User::factory()->create();
    $eligible = Seminar::factory()->create(['scheduled_at' => now()->subDays(3)]);
    $rated = Seminar::factory()->create(['scheduled_at' => now()->subDays(4)]);
    Registration::factory()->for($user)->for($eligible)->create(['present' => true]);
    Registration::factory()->for($user)->for($rated)->create(['present' => true]);
    Rating::factory()->for($user)->for($rated)->create();

    expect($this->service->pendingEvaluationsFor($user)->pluck('seminar_id')->all())
        ->toEqual([$eligible->id]);
});

it('computes average score for a seminar', function () {
    $seminar = Seminar::factory()->create();
    Rating::factory()->for($seminar)->count(3)->state(new Sequence(
        ['score' => 5], ['score' => 3], ['score' => 4]
    ))->create();

    expect($this->service->averageScore($seminar))->toBe(4.0);
});

it('returns null when the seminar has no ratings', function () {
    $seminar = Seminar::factory()->create();
    expect($this->service->averageScore($seminar))->toBeNull();
});
