<?php

use App\Enums\AuditEvent;
use App\Enums\ConsentType;
use App\Models\AuditLog;
use App\Models\UserConsent;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('records consent for an authenticated user', function () {
    $user = actingAsUser();

    $response = postJson('/api/consents', [
        'type' => ConsentType::CookiesAnalytics->value,
        'granted' => true,
        'version' => '1.0',
    ]);

    $response->assertSuccessful();
    expect($user->consents()->count())->toBe(1);

    $consent = $user->consents()->first();
    expect($consent->type)->toBe(ConsentType::CookiesAnalytics)
        ->and($consent->granted)->toBeTrue()
        ->and($consent->source)->toBe('preference_center');

    expect(AuditLog::where('event_name', AuditEvent::ConsentGranted->value)->exists())->toBeTrue();
});

it('records revocation', function () {
    actingAsUser();

    postJson('/api/consents', [
        'type' => ConsentType::CookiesAnalytics->value,
        'granted' => false,
        'version' => '1.0',
    ])->assertSuccessful();

    expect(AuditLog::where('event_name', AuditEvent::ConsentRevoked->value)->exists())->toBeTrue();
});

it('accepts anonymous consent via anonymous_id', function () {
    $response = postJson('/api/consents', [
        'type' => ConsentType::CookiesFunctional->value,
        'granted' => true,
        'version' => '1.0',
        'anonymous_id' => 'anon-abc-123',
    ]);

    $response->assertSuccessful();
    expect(UserConsent::where('anonymous_id', 'anon-abc-123')->exists())->toBeTrue();
});

it('validates known consent types', function () {
    actingAsUser();

    postJson('/api/consents', [
        'type' => 'unknown_type',
        'granted' => true,
    ])->assertUnprocessable();
});

it('lists consents for authenticated user', function () {
    $user = actingAsUser();
    UserConsent::factory()->for($user)->granted()->create(['type' => ConsentType::PrivacyPolicy]);
    UserConsent::factory()->for($user)->revoked()->create(['type' => ConsentType::CookiesAnalytics]);

    $response = getJson('/api/consents');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('requires auth for listing consents', function () {
    getJson('/api/consents')->assertUnauthorized();
});
