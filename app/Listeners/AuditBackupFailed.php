<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Spatie\Backup\Events\BackupHasFailed;

class AuditBackupFailed
{
    public function handle(BackupHasFailed $event): void
    {
        try {
            AuditLog::record(
                AuditEvent::DatabaseBackupFailed,
                AuditEventType::System,
                eventData: [
                    'error' => $event->exception->getMessage(),
                    'disk' => $event->diskName,
                ],
                refId: 'backup:failed:'.($event->diskName ?? 'unknown').':'.md5($event->exception->getMessage()).':'.Carbon::now()->format('Y-m-d'),
            );
        } catch (UniqueConstraintViolationException) {
            // Laravel event auto-discovery registers this listener alongside
            // the explicit AppServiceProvider binding, so a single failure can
            // dispatch twice. The unique index on `audit_logs.ref_id` absorbs
            // the duplicate. Mirrors the AuditEmailSent / AuditNotificationSent
            // guards for the same double-dispatch behaviour.
        }
    }
}
