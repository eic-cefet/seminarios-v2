<?php

namespace App\Providers;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class CloudWatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->shouldInitialize()) {
            return;
        }

        $config = config('logging.channels.cloudwatch');
        $cacheKey = 'cloudwatch:initialized:'.$config['group_name'].':'.$config['stream_name'];

        Cache::remember($cacheKey, now()->addYear(), function () use ($config) {
            $this->ensureResourcesExist($config);

            return true;
        });
    }

    protected function shouldInitialize(): bool
    {
        if (! config('logging.enable_cloudwatch_boot', true)) {
            return false;
        }

        $config = config('logging.channels.cloudwatch');

        if (! $config) {
            return false;
        }

        if (empty($config['credentials']['key'])) {
            return false;
        }

        $logStack = config('logging.channels.stack.channels', []);
        $defaultChannel = config('logging.default');

        return $defaultChannel === 'cloudwatch' || in_array('cloudwatch', $logStack);
    }

    protected function ensureResourcesExist(array $config): void
    {
        try {
            $client = $this->createClient($config);

            try {
                $client->createLogGroup(['logGroupName' => $config['group_name']]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() !== 'ResourceAlreadyExistsException') {
                    throw $e;
                }
            }

            try {
                $client->createLogStream([
                    'logGroupName' => $config['group_name'],
                    'logStreamName' => $config['stream_name'],
                ]);
            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() !== 'ResourceAlreadyExistsException') {
                    throw $e;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function createClient(array $config): CloudWatchLogsClient
    {
        return new CloudWatchLogsClient([
            'region' => $config['region'],
            'version' => $config['version'] ?? 'latest',
            'credentials' => $config['credentials'],
        ]);
    }
}
