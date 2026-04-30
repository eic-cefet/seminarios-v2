<?php

use App\Providers\AppServiceProvider;
use App\Providers\CloudWatchServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ScrambleServiceProvider;

return [
    AppServiceProvider::class,
    CloudWatchServiceProvider::class,
    FortifyServiceProvider::class,
    ScrambleServiceProvider::class,
];
