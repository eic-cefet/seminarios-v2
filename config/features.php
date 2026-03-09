<?php

return [
    'sentiment_analysis' => [
        'enabled' => env('FEATURE_SENTIMENT_ANALYSIS', true),
        'sample_rate' => env('FEATURE_SENTIMENT_SAMPLE_RATE', 50),
    ],
];
