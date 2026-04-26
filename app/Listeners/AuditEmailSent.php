<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Services\FeatureFlags;
use App\Services\IpHasher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

class AuditEmailSent
{
    public function handle(MessageSent $event): void
    {
        if (! FeatureFlags::enabled('email_audit')) {
            return;
        }

        $message = $event->sent->getOriginalMessage();
        $headers = $message->getHeaders();

        $refIdHeader = $headers->get('X-Entity-Ref-ID');

        if (! $refIdHeader) {
            return;
        }

        $mailClassHeader = $headers->get('X-Mail-Class');

        try {
            AuditLog::record(
                AuditEvent::EmailSent,
                AuditEventType::System,
                eventData: [
                    'mail' => $mailClassHeader?->getBodyAsString(),
                    'to' => array_map(
                        fn (Address $address) => app(IpHasher::class)->hashOpaque($address->getAddress()),
                        $message->getTo(),
                    ),
                    'recipient_count' => count($message->getTo()),
                    'subject' => $message->getSubject(),
                ],
                refId: $refIdHeader->getBodyAsString(),
            );
        } catch (UniqueConstraintViolationException) {
            // A prior delivery of the same logical email already produced an
            // audit row. Queue retries or accidental double-dispatch are
            // absorbed by the unique index on `audit_logs.ref_id`.
        }
    }
}
