<?php

use App\Enums\AuditEvent;
use App\Jobs\ExportUserDataJob;
use App\Mail\DataExportFailed;
use App\Mail\DataExportReady;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\User;
use App\Services\UserDataExportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('uploads the ZIP to S3 and emails a signed URL', function () {
    Storage::fake('s3');
    Mail::fake();

    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    (new ExportUserDataJob($request->id))->handle(
        app(UserDataExportService::class),
    );

    $request->refresh();
    expect($request->status)->toBe(DataExportRequest::STATUS_COMPLETED)
        ->and($request->file_path)->toStartWith('lgpd-exports/')
        ->and($request->expires_at)->not->toBeNull();

    Storage::disk('s3')->assertExists($request->file_path);
    Mail::assertQueued(DataExportReady::class, fn ($mail) => $mail->hasTo($user->email));
    expect(AuditLog::where('event_name', AuditEvent::DataExportDelivered->value)->exists())->toBeTrue();
});

it('marks the request failed and emails on error', function () {
    Storage::fake('s3');
    Mail::fake();

    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    $service = Mockery::mock(UserDataExportService::class);
    $service->shouldReceive('writeZip')->andThrow(new RuntimeException('disk full'));

    try {
        (new ExportUserDataJob($request->id))->handle($service);
    } catch (Throwable) {
        // expected to rethrow
    }

    expect($request->fresh()->status)->toBe(DataExportRequest::STATUS_FAILED);
    Mail::assertQueued(DataExportFailed::class);
});

it('queues an export job when the user requests one', function () {
    Queue::fake();
    $user = actingAsUser();

    $response = $this->postJson('/api/profile/data-export');

    $response->assertSuccessful();
    expect(DataExportRequest::where('user_id', $user->id)->count())->toBe(1);
    Queue::assertPushed(ExportUserDataJob::class);
    expect(AuditLog::where('event_name', AuditEvent::DataExportRequested->value)->exists())->toBeTrue();
});

it('rate-limits export requests to one every 24h', function () {
    Queue::fake();
    $user = actingAsUser();
    DataExportRequest::factory()->for($user)->create([
        'status' => DataExportRequest::STATUS_QUEUED,
        'created_at' => now()->subHour(),
    ]);

    $this->postJson('/api/profile/data-export')->assertStatus(429);
});

it('lists past export requests', function () {
    $user = actingAsUser();
    DataExportRequest::factory()->for($user)->count(2)->create();

    $response = $this->getJson('/api/profile/data-export');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});
