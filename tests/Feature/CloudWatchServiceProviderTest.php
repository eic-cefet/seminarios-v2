<?php

use App\Providers\CloudWatchServiceProvider;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('skips initialization when running tests by default', function () {
    $provider = new CloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when cloudwatch channel is not configured', function () {
    $this->app->instance('env', 'production');

    config(['logging.channels.cloudwatch' => null]);

    $provider = new CloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when credentials are empty', function () {
    $this->app->instance('env', 'production');

    config([
        'logging.channels.cloudwatch' => [
            'sdk' => ['credentials' => ['key' => '']],
        ],
    ]);

    $provider = new CloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when cloudwatch is not in log stack', function () {
    $this->app->instance('env', 'production');

    config([
        'logging.default' => 'stack',
        'logging.channels.stack.channels' => ['single'],
        'logging.channels.cloudwatch' => [
            'sdk' => ['credentials' => ['key' => 'test-key']],
        ],
    ]);

    $provider = new CloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('caches initialization result with correct key format', function () {
    $groupName = 'test-group';
    $streamName = 'test-stream';
    $cacheKey = "cloudwatch:initialized:{$groupName}:{$streamName}";

    expect(Cache::has($cacheKey))->toBeFalse();

    Cache::put($cacheKey, true, now()->addYear());

    expect(Cache::has($cacheKey))->toBeTrue();
    expect(Cache::get($cacheKey))->toBeTrue();
});

it('uses separate cache keys for different streams', function () {
    $serverKey = 'cloudwatch:initialized:seminarios-eic:server';
    $workerKey = 'cloudwatch:initialized:seminarios-eic:worker';

    Cache::put($serverKey, true, now()->addYear());

    expect(Cache::has($serverKey))->toBeTrue();
    expect(Cache::has($workerKey))->toBeFalse();
});
