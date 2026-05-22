<?php

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\IpHasher;
use App\Services\UserConsentService;
use Illuminate\Http\Request;

it('records the two signup consents with the given source', function () {
    $user = User::factory()->create();
    $request = Request::create('/register', 'POST', server: [
        'REMOTE_ADDR' => '203.0.113.1',
        'HTTP_USER_AGENT' => 'test-agent/1.0',
    ]);

    app(UserConsentService::class)->recordSignupConsents($user, $request, source: 'registration');

    expect(UserConsent::query()->where('user_id', $user->id)->where('source', 'registration')->count())
        ->toBe(2);

    $types = UserConsent::query()->where('user_id', $user->id)->pluck('type')->all();
    expect($types)->toContain(ConsentType::TermsOfService)
        ->and($types)->toContain(ConsentType::PrivacyPolicy);
});

it('records consents with the hashed IP and user-agent from the request', function () {
    $user = User::factory()->create();
    $request = Request::create('/register', 'POST', server: [
        'REMOTE_ADDR' => '198.51.100.5',
        'HTTP_USER_AGENT' => 'browser-x/2',
    ]);

    app(UserConsentService::class)->recordSignupConsents($user, $request, source: 'oauth');

    $hasher = app(IpHasher::class);
    $expectedIpHash = $hasher->hash('198.51.100.5');
    $expectedUaHash = $hasher->hashOpaque('browser-x/2');

    $consent = UserConsent::query()->where('user_id', $user->id)->first();
    expect($consent->source)->toBe('oauth')
        ->and($consent->granted)->toBeTrue()
        ->and($consent->ip_hash)->toBe($expectedIpHash)
        ->and($consent->user_agent_hash)->toBe($expectedUaHash);
});

it('stores the configured version for each consent type', function () {
    config()->set('lgpd.versions.terms_of_service', '2.5');
    config()->set('lgpd.versions.privacy_policy', '3.1');

    $user = User::factory()->create();
    $request = Request::create('/register', 'POST', server: [
        'REMOTE_ADDR' => '203.0.113.2',
        'HTTP_USER_AGENT' => 'ua/1',
    ]);

    app(UserConsentService::class)->recordSignupConsents($user, $request, source: 'registration');

    $terms = UserConsent::query()->where('user_id', $user->id)->where('type', ConsentType::TermsOfService)->first();
    $privacy = UserConsent::query()->where('user_id', $user->id)->where('type', ConsentType::PrivacyPolicy)->first();

    expect($terms->version)->toBe('2.5')
        ->and($privacy->version)->toBe('3.1');
});
