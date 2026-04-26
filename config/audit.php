<?php

return [
    'hash_salt' => env('AUDIT_HASH_SALT', config('app.key')),

    'retention' => [
        'default' => 90,
        'security' => 365,
        'system' => 30,
    ],
];
