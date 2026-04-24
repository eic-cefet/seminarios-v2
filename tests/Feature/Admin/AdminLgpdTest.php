<?php

use App\Enums\AuditEvent;
use App\Jobs\AnonymizeUserJob;
use App\Jobs\ExportUserDataJob;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('returns LGPD summary for a user', function () {
    actingAsAdmin();
    $target = User::factory()->create();
    UserConsent::factory()->for($target)->granted()->count(3)->create();

    $response = getJson("/api/admin/users/{$target->id}/lgpd");

    $response->assertSuccessful();
    expect($response->json('data.consents'))->toHaveCount(3);
});

it('queues export on behalf of user', function () {
    Queue::fake();
    actingAsAdmin();
    $target = User::factory()->create();

    postJson("/api/admin/users/{$target->id}/lgpd/export")->assertAccepted();

    Queue::assertPushed(ExportUserDataJob::class);
    expect(
        AuditLog::where('event_name', AuditEvent::DataExportRequested->value)
            ->where('auditable_type', DataExportRequest::class)
            ->exists()
    )->toBeTrue();
});

it('triggers immediate anonymization with reason', function () {
    Queue::fake();
    actingAsAdmin();
    $target = User::factory()->create();

    postJson("/api/admin/users/{$target->id}/lgpd/anonymize", [
        'reason' => 'Solicitação via ANPD — ticket #123',
    ])->assertAccepted();

    Queue::assertPushed(AnonymizeUserJob::class, fn ($job) => $job->userId === $target->id);
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionExecuted->value)->exists())->toBeTrue();
});

it('requires a reason for anonymization', function () {
    actingAsAdmin();
    $target = User::factory()->create();

    postJson("/api/admin/users/{$target->id}/lgpd/anonymize", [])
        ->assertUnprocessable();
});

it('rejects non-admin callers', function () {
    $target = User::factory()->create();
    actingAsUser();

    postJson("/api/admin/users/{$target->id}/lgpd/export")->assertForbidden();
    getJson("/api/admin/users/{$target->id}/lgpd")->assertForbidden();
});
