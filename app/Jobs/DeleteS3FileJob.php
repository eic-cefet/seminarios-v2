<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class DeleteS3FileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $path,
        public string $disk = 's3',
    ) {}

    public function handle(): void
    {
        $this->setAuditContext();

        Storage::disk($this->disk)->delete($this->path);

        AuditLog::record(AuditEvent::S3FileDeleted, AuditEventType::System, eventData: [
            'path' => $this->path,
            'disk' => $this->disk,
        ]);
    }
}
