<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS Secret Env Overrides
    |--------------------------------------------------------------------------
    |
    | When AWS_ENV_SECRET_ID is set, AwsSecretEnvLoader (bootstrap/app.php)
    | fetches that Secrets Manager secret before configuration loads, so the
    | values are baked into the config cache. The env-secrets:refresh command
    | reads this baked secret id to decide whether periodic refreshing is on.
    |
    */

    'secret_id' => env('AWS_ENV_SECRET_ID'),

];
