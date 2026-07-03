<?php

use App\Services\ConfigCacheRefresher;
use Illuminate\Support\Facades\Artisan;

it('re-caches configuration and signals a queue restart, in that order', function () {
    Artisan::shouldReceive('call')->once()->ordered()->with('config:cache');
    Artisan::shouldReceive('call')->once()->ordered()->with('queue:restart');

    (new ConfigCacheRefresher)->refresh();
});
