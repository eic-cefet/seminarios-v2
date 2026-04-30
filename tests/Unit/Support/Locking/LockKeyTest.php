<?php

use App\Models\Subject;
use App\Support\Locking\LockKey;

it('produces deterministic keys for slug generation', function () {
    expect(LockKey::slugGeneration(Subject::class, 'fisica-quantica'))
        ->toBe('lock:slug:App\\Models\\Subject:fisica-quantica');
});

it('produces deterministic keys for seminar registration', function () {
    expect(LockKey::seminarRegistration(42))->toBe('lock:registration:seminar:42');
});

it('produces deterministic keys for rating creation', function () {
    expect(LockKey::ratingCreation(7, 9))->toBe('lock:rating:seminar:7:user:9');
});

it('produces deterministic keys for certificate generation', function () {
    expect(LockKey::certificateGeneration(33))->toBe('lock:certificate:registration:33');
});

it('produces deterministic keys for sentiment analysis', function () {
    expect(LockKey::ratingSentiment(99))->toBe('lock:sentiment:rating:99');
});

it('produces deterministic keys for external API idempotency', function () {
    $hash = hash('sha256', 'my-key');

    expect(LockKey::externalIdempotency('tok-1', 'my-key'))
        ->toBe('lock:external_api:idempotency:tok-1:'.$hash);
});
