<?php

use App\Enums\ConsentType;

it('lists all consent types used by the system', function () {
    expect(ConsentType::cases())->toHaveCount(5);

    expect(ConsentType::TermsOfService->value)->toBe('terms_of_service')
        ->and(ConsentType::PrivacyPolicy->value)->toBe('privacy_policy')
        ->and(ConsentType::CookiesFunctional->value)->toBe('cookies_functional')
        ->and(ConsentType::CookiesAnalytics->value)->toBe('cookies_analytics')
        ->and(ConsentType::AiSentimentAnalysis->value)->toBe('ai_sentiment_analysis');
});

it('marks terms and privacy as required for account creation', function () {
    expect(ConsentType::TermsOfService->isRequiredAtSignup())->toBeTrue()
        ->and(ConsentType::PrivacyPolicy->isRequiredAtSignup())->toBeTrue()
        ->and(ConsentType::CookiesAnalytics->isRequiredAtSignup())->toBeFalse()
        ->and(ConsentType::AiSentimentAnalysis->isRequiredAtSignup())->toBeFalse();
});
