<?php

use App\Enums\AuditEvent;

it('includes LGPD consent and data-rights events', function () {
    expect(AuditEvent::ConsentGranted->value)->toBe('lgpd.consent_granted')
        ->and(AuditEvent::ConsentRevoked->value)->toBe('lgpd.consent_revoked')
        ->and(AuditEvent::DataExportRequested->value)->toBe('lgpd.data_export_requested')
        ->and(AuditEvent::DataExportDelivered->value)->toBe('lgpd.data_export_delivered')
        ->and(AuditEvent::AccountDeletionRequested->value)->toBe('lgpd.account_deletion_requested')
        ->and(AuditEvent::AccountDeletionCancelled->value)->toBe('lgpd.account_deletion_cancelled')
        ->and(AuditEvent::AccountAnonymized->value)->toBe('lgpd.account_anonymized');
});
