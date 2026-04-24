<?php

namespace App\Enums;

enum ConsentType: string
{
    case TermsOfService = 'terms_of_service';
    case PrivacyPolicy = 'privacy_policy';
    case CookiesFunctional = 'cookies_functional';
    case CookiesAnalytics = 'cookies_analytics';
    case AiSentimentAnalysis = 'ai_sentiment_analysis';

    public function isRequiredAtSignup(): bool
    {
        return match ($this) {
            self::TermsOfService, self::PrivacyPolicy => true,
            default => false,
        };
    }
}
