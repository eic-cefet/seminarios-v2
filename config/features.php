<?php

return [
    'sentiment_analysis' => [
        'enabled' => env('FEATURE_SENTIMENT_ANALYSIS', true),
        'sample_rate' => env('FEATURE_SENTIMENT_ANALYSIS_SAMPLE_RATE', 50),
    ],
    'email_audit' => [
        'enabled' => env('FEATURE_EMAIL_AUDIT', true),
    ],
    'notification_audit' => [
        'enabled' => env('FEATURE_NOTIFICATION_AUDIT', true),
    ],
    'env_secrets_setup' => [
        'enabled' => env('FEATURE_ENV_SECRETS_SETUP', true),
    ],
    'database_backup' => [
        'enabled' => env('FEATURE_DATABASE_BACKUP', true),
    ],
];
