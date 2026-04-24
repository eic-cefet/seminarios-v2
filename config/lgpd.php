<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encarregado (Data Protection Officer) contact
    |--------------------------------------------------------------------------
    | LGPD Art. 41 — the controller must designate a DPO and publish their
    | contact details. Rendered on the Privacy Policy page.
    */
    'encarregado' => [
        'name' => env('LGPD_DPO_NAME', 'Encarregado de Proteção de Dados'),
        'email' => env('LGPD_DPO_EMAIL', 'lgpd@eic-seminarios.com'),
        'phone' => env('LGPD_DPO_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention periods
    |--------------------------------------------------------------------------
    | Used by app/Console/Commands/LgpdPurgeCommand (phase 3). Kept here so
    | the public privacy page can render the same numbers.
    */
    'retention' => [
        'audit_logs_days' => (int) env('LGPD_RETENTION_AUDIT_LOGS_DAYS', 90),
        'sessions_days' => (int) env('LGPD_RETENTION_SESSIONS_DAYS', 30),
        'personal_access_tokens_days' => (int) env('LGPD_RETENTION_API_TOKENS_DAYS', 180),
        'presence_links_days' => (int) env('LGPD_RETENTION_PRESENCE_LINKS_DAYS', 30),
        'account_deletion_grace_days' => (int) env('LGPD_DELETION_GRACE_DAYS', 30),
        'data_export_link_hours' => (int) env('LGPD_DATA_EXPORT_LINK_HOURS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'cookie_banner' => (bool) env('LGPD_COOKIE_BANNER_ENABLED', true),
        'ai_sentiment_opt_in' => (bool) env('LGPD_AI_SENTIMENT_OPT_IN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal document versions
    |--------------------------------------------------------------------------
    | Bump these when you materially change the corresponding document — the
    | consent UI re-prompts users whose stored acceptance is older.
    */
    'versions' => [
        'privacy_policy' => env('LGPD_PRIVACY_POLICY_VERSION', '1.0'),
        'terms_of_service' => env('LGPD_TERMS_VERSION', '1.0'),
    ],
];
