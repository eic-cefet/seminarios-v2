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

    private function shouldInitialize(): bool
    {
        if ($this->app->runningUnitTests()) {
            return false;
        }

        $config = config('logging.channels.cloudwatch');

        if (! $config) {
            return false;
        }

        if (empty($config['sdk']['credentials']['key'])) {
            return false;
        }

        $logStack = config('logging.channels.stack.channels', []);
        $defaultChannel = config('logging.default');

        return $defaultChannel === 'cloudwatch' || in_array('cloudwatch', $logStack);
    }

    private function ensureResourcesExist(array $config): void
    {
        try {
            $client = new CloudWatchLogsClient([
                'region' => $config['sdk']['region'],
                'version' => 'latest',
                'credentials' => $config['sdk']['credentials'],
            ]);

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
}
