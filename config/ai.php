<?php

return [
    'api_key' => env('AI_API_KEY'),
    'base_url' => env('AI_API_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
];
