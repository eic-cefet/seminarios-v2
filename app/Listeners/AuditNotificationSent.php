<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Services\FeatureFlags;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Notifications\Events\NotificationSent;

class AuditNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        if (! FeatureFlags::enabled('notification_audit')) {
            return;
        }

        if ($event->channel !== 'database') {
            return;
        }

        try {
            AuditLog::record(
                AuditEvent::NotificationSent,
                AuditEventType::System,
                $event->notifiable,
                [
                    'notification' => $event->notification::class,
                    'channel' => $event->channel,
                    'notifiable_id' => $event->notifiable->getKey(),
                ],
                refId: 'notification:'.$event->notification->id.':'.$event->channel,
            );
        } catch (UniqueConstraintViolationException) {
            // A prior delivery of the same logical notification already
            // produced an audit row. Queue retries or duplicate event
            // dispatches are absorbed by the unique index on
            // `audit_logs.ref_id`. Mirrors the AuditEmailSent guard added
            // in PR #119 for `MessageSent` duplication.
        }
    }
}
