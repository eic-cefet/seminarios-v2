<?php

return [
    'sentiment_analysis' => [
        'enabled' => env('FEATURE_SENTIMENT_ANALYSIS', true),
        'sample_rate' => env('FEATURE_SENTIMENT_ANALYSIS_SAMPLE_RATE', 50),
    ],
    'email_audit' => [
        'enabled' => env('FEATURE_EMAIL_AUDIT', true),
    ],
];
