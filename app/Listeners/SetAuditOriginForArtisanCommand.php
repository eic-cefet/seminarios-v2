<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Context;

class SetAuditOriginForArtisanCommand
{
    public function handle(CommandStarting $event): void
    {
        $name = $event->command;
        if ($name === null || $name === '') {
            return;
        }

        $command = collect(app(Kernel::class)->all())->get($name);
        if ($command !== null) {
            Context::add('audit.origin', $command::class);
        }
    }
}
