<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;

beforeEach(fn () => actingAsAdmin());

it('audits admin viewing an LGPD record', function () {
    $user = User::factory()->create();

    $this->getJson("/api/admin/users/{$user->id}/lgpd")->assertSuccessful();

    $log = AuditLog::where('event_name', AuditEvent::LgpdRecordViewedByAdmin->value)
        ->where('auditable_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
});
