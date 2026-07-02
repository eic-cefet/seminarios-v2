<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Spatie\Backup\Events\BackupWasSuccessful;

class AuditBackupCompleted
{
    public function handle(BackupWasSuccessful $event): void
    {
        try {
            AuditLog::record(
                AuditEvent::DatabaseBackupCompleted,
                AuditEventType::System,
                eventData: [
                    'disk' => $event->diskName,
                    'backup_name' => $event->backupName,
                ],
                refId: 'backup:completed:'.$event->diskName.':'.$event->backupName.':'.Carbon::now()->format('Y-m-d'),
            );
        } catch (UniqueConstraintViolationException) {
            // Laravel event auto-discovery registers this listener alongside
            // the explicit AppServiceProvider binding, so a single backup can
            // dispatch twice. The unique index on `audit_logs.ref_id` absorbs
            // the duplicate. Mirrors the AuditEmailSent / AuditNotificationSent
            // guards for the same double-dispatch behaviour.
        }
    }
}
