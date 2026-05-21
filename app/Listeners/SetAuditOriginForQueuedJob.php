<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Context;

class SetAuditOriginForQueuedJob
{
    public function handle(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $command = $payload['data']['commandName'] ?? null;

        if (is_string($command) && $command !== '') {
            Context::add('audit.origin', $command);
        }
    }
}
