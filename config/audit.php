<?php

return [
    'hash_salt' => env('AUDIT_HASH_SALT'),

    'retention' => [
        'default' => 90,
        'security' => 365,
        'system' => 30,
    ],

    'tiers' => [
        'security' => [
            'user.login_failed',
            'user.mfa_challenge_failed',
            'user.password_changed',
            'user.password_reset_by_admin',
            'user.role_assigned',
            'user.role_revoked',
            'admin.user_deleted',
            'admin.user_restored',
            'lgpd.account_deletion_executed',
            'lgpd.account_anonymized',
            'security.authorization_denied',
        ],
        'system' => [
            'email.sent',
            'notification.sent',
            'seminar_alert.dispatched',
            's3.file_deleted',
        ],
    ],
];
