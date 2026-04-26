<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastWorkshopLaunchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $workshopId) {}

    public function handle(): void
    {
        User::query()->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                SendWorkshopAvailableJob::dispatch($user, $this->workshopId);
            }
        });
    }
}
