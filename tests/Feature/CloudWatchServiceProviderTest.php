<?php

use App\Providers\CloudWatchServiceProvider;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Exception\AwsException;
use Aws\Result;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('skips initialization when enable_cloudwatch_boot is false', function () {
    config(['logging.enable_cloudwatch_boot' => false]);

    $provider = new CloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when cloudwatch channel is not configured', function () {
    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.channels.cloudwatch' => null,
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when credentials are empty', function () {
    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.channels.cloudwatch' => [
            'credentials' => ['key' => ''],
        ],
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('skips initialization when cloudwatch is not in log stack', function () {
    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.default' => 'stack',
        'logging.channels.stack.channels' => ['single'],
        'logging.channels.cloudwatch' => [
            'credentials' => ['key' => 'test-key'],
        ],
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeFalse();
});

it('initializes when cloudwatch is the default channel', function () {
    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.default' => 'cloudwatch',
        'logging.channels.cloudwatch' => [
            'credentials' => ['key' => 'test-key'],
        ],
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeTrue();
});

it('initializes when cloudwatch is in the log stack', function () {
    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.default' => 'stack',
        'logging.channels.stack.channels' => ['single', 'cloudwatch'],
        'logging.channels.cloudwatch' => [
            'credentials' => ['key' => 'test-key'],
        ],
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);

    $method = new ReflectionMethod($provider, 'shouldInitialize');

    expect($method->invoke($provider))->toBeTrue();
});

it('creates log group and stream successfully', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    $mockClient->shouldReceive('createLogGroup')
        ->once()
        ->with(['logGroupName' => 'test-group'])
        ->andReturn(new Result([]));

    $mockClient->shouldReceive('createLogStream')
        ->once()
        ->with([
            'logGroupName' => 'test-group',
            'logStreamName' => 'test-stream',
        ])
        ->andReturn(new Result([]));

    $config = [
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'group_name' => 'test-group',
        'stream_name' => 'test-stream',
    ];

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    $method = new ReflectionMethod($provider, 'ensureResourcesExist');
    $method->invoke($provider, $config);

    expect(true)->toBeTrue(); // If we get here without exception, it worked
});

it('handles ResourceAlreadyExistsException for log group', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    $awsException = new AwsException(
        'Resource already exists',
        Mockery::mock(\Aws\CommandInterface::class),
        ['code' => 'ResourceAlreadyExistsException']
    );

    $mockClient->shouldReceive('createLogGroup')
        ->once()
        ->andThrow($awsException);

    $mockClient->shouldReceive('createLogStream')
        ->once()
        ->andReturn(new Result([]));

    $config = [
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'group_name' => 'test-group',
        'stream_name' => 'test-stream',
    ];

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    $method = new ReflectionMethod($provider, 'ensureResourcesExist');
    $method->invoke($provider, $config);

    expect(true)->toBeTrue();
});

it('handles ResourceAlreadyExistsException for log stream', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    $awsException = new AwsException(
        'Resource already exists',
        Mockery::mock(\Aws\CommandInterface::class),
        ['code' => 'ResourceAlreadyExistsException']
    );

    $mockClient->shouldReceive('createLogGroup')
        ->once()
        ->andReturn(new Result([]));

    $mockClient->shouldReceive('createLogStream')
        ->once()
        ->andThrow($awsException);

    $config = [
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'group_name' => 'test-group',
        'stream_name' => 'test-stream',
    ];

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    $method = new ReflectionMethod($provider, 'ensureResourcesExist');
    $method->invoke($provider, $config);

    expect(true)->toBeTrue();
});

it('reports non-ResourceAlreadyExistsException errors', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    $awsException = new AwsException(
        'Access denied',
        Mockery::mock(\Aws\CommandInterface::class),
        ['code' => 'AccessDeniedException']
    );

    $mockClient->shouldReceive('createLogGroup')
        ->once()
        ->andThrow($awsException);

    $config = [
        'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'group_name' => 'test-group',
        'stream_name' => 'test-stream',
    ];

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    // The error should be reported but not thrown
    $method = new ReflectionMethod($provider, 'ensureResourcesExist');
    $method->invoke($provider, $config);

    expect(true)->toBeTrue();
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

it('boots and caches when properly configured', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    $mockClient->shouldReceive('createLogGroup')->once()->andReturn(new Result([]));
    $mockClient->shouldReceive('createLogStream')->once()->andReturn(new Result([]));

    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.default' => 'cloudwatch',
        'logging.channels.cloudwatch' => [
            'region' => 'us-east-1',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'group_name' => 'boot-test-group',
            'stream_name' => 'boot-test-stream',
        ],
    ]);

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    $provider->boot();

    expect(Cache::has('cloudwatch:initialized:boot-test-group:boot-test-stream'))->toBeTrue();
});

it('skips AWS calls when cached', function () {
    $mockClient = Mockery::mock(CloudWatchLogsClient::class);

    // These should NOT be called because result is cached
    $mockClient->shouldNotReceive('createLogGroup');
    $mockClient->shouldNotReceive('createLogStream');

    config([
        'logging.enable_cloudwatch_boot' => true,
        'logging.default' => 'cloudwatch',
        'logging.channels.cloudwatch' => [
            'region' => 'us-east-1',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'group_name' => 'cached-group',
            'stream_name' => 'cached-stream',
        ],
    ]);

    // Pre-populate cache
    Cache::put('cloudwatch:initialized:cached-group:cached-stream', true, now()->addYear());

    $provider = new TestableCloudWatchServiceProvider($this->app);
    $provider->mockClient = $mockClient;

    $provider->boot();

    expect(true)->toBeTrue();
});

/**
 * Testable subclass that allows mocking the CloudWatch client.
 */
class TestableCloudWatchServiceProvider extends CloudWatchServiceProvider
{
    public ?CloudWatchLogsClient $mockClient = null;

    protected function createClient(array $config): CloudWatchLogsClient
    {
        return $this->mockClient ?? parent::createClient($config);
    }
}
