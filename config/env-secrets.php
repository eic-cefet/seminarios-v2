<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS Secret Env Overrides
    |--------------------------------------------------------------------------
    |
    | When AWS_ENV_SECRET_ID is set, AwsSecretEnvLoader (bootstrap/app.php)
    | fetches that Secrets Manager secret before configuration loads, so the
    | values are baked into the config cache. These baked copies are the ONLY
    | reliable source at runtime on cached-config deployments (Laravel skips
    | loading .env entirely when config is cached): the env-secrets:refresh
    | command and the admin setup screen both read them from here.
    |
    */

    'secret_id' => env('AWS_ENV_SECRET_ID'),
    'region' => env('AWS_ENV_SECRET_REGION'),
    'access_key_id' => env('AWS_ENV_SECRET_ACCESS_KEY_ID'),
    'secret_access_key' => env('AWS_ENV_SECRET_SECRET_ACCESS_KEY'),

];
