<?php

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;

it('records a consent grant for a user', function () {
    $user = User::factory()->create();

    $consent = UserConsent::factory()->for($user)->create([
        'type' => ConsentType::PrivacyPolicy,
        'granted' => true,
        'version' => '1.0',
    ]);

    expect($consent->user_id)->toBe($user->id)
        ->and($consent->type)->toBe(ConsentType::PrivacyPolicy)
        ->and($consent->granted)->toBeTrue()
        ->and($consent->version)->toBe('1.0')
        ->and($user->consents()->count())->toBe(1);
});

it('supports revoking a previously granted consent', function () {
    $user = User::factory()->create();
    UserConsent::factory()->for($user)->granted()->create(['type' => ConsentType::CookiesAnalytics]);

    UserConsent::factory()->for($user)->revoked()->create(['type' => ConsentType::CookiesAnalytics]);

    $latest = $user->consents()
        ->where('type', ConsentType::CookiesAnalytics)
        ->latest()
        ->first();

    expect($latest->granted)->toBeFalse();
});
