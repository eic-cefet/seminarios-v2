<?php

use App\Models\Seminar;
use App\Support\PresenceLinkPolicy;
use Illuminate\Support\Carbon;

it('uses scheduled_at + 4h when that is later than now + 1h', function () {
    Carbon::setTestNow('2026-05-21 10:00:00');
    $seminar = new Seminar(['scheduled_at' => Carbon::parse('2026-05-21 10:00:00')]);

    expect(PresenceLinkPolicy::expiresAt($seminar)->toDateTimeString())
        ->toBe('2026-05-21 14:00:00');
});

it('uses now + 1h as a floor when the seminar already ended', function () {
    Carbon::setTestNow('2026-05-21 18:00:00');
    $seminar = new Seminar(['scheduled_at' => Carbon::parse('2026-05-21 10:00:00')]);

    expect(PresenceLinkPolicy::expiresAt($seminar)->toDateTimeString())
        ->toBe('2026-05-21 19:00:00');
});

it('declares a default active=true policy', function () {
    expect(PresenceLinkPolicy::defaultActive())->toBeTrue();
});
