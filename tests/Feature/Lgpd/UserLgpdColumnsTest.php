<?php

use App\Models\User;

it('supports anonymization_requested_at and anonymized_at on User', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'anonymization_requested_at' => now(),
    ])->save();

    $fresh = $user->fresh();
    expect($fresh->anonymization_requested_at)->not->toBeNull()
        ->and($fresh->isAnonymizationPending())->toBeTrue();

    $fresh->forceFill(['anonymized_at' => now()])->save();
    expect($fresh->fresh()->isAnonymized())->toBeTrue();
});
