<?php

use App\Enums\AuditEvent;
use App\Jobs\DeleteS3FileJob;
use App\Jobs\ExportUserDataJob;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\User;
use App\Services\UserDataExportService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('uploads the ZIP to S3 and emails a signed URL', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

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
    Mail::assertSent(ReportReady::class, fn ($mail) => $mail->hasTo($user->email));
    Queue::assertPushed(DeleteS3FileJob::class);
    expect(AuditLog::where('event_name', AuditEvent::DataExportDelivered->value)->exists())->toBeTrue();
});

it('honours lgpd.retention.data_export_link_hours and schedules S3 cleanup at the same offset', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    config()->set('lgpd.retention.data_export_link_hours', 6);

    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    (new ExportUserDataJob($request->id))->handle(app(UserDataExportService::class));

    $request->refresh();
    // expires_at must be approximately now + configured hours (within a 10-second window)
    expect($request->expires_at->diffInSeconds(now()->addHours(6), true))->toBeLessThan(10);

    Queue::assertPushed(DeleteS3FileJob::class, fn ($job) => $job->path === $request->file_path);
});

it('throws a RuntimeException when S3 does not store the file', function () {
    Mail::fake();
    Queue::fake();

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('put')->once()->andReturn(true);
    $disk->shouldReceive('exists')->once()->andReturn(false);

    Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    expect(fn () => (new ExportUserDataJob($request->id))->handle(app(UserDataExportService::class)))
        ->toThrow(RuntimeException::class, 'Failed to store data export on S3');
});

it('marks the request failed via the failed() hook after final retry', function () {
    Storage::fake('s3');
    Mail::fake();

    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    (new ExportUserDataJob($request->id))->failed(new RuntimeException('disk full'));

    expect($request->fresh()->status)->toBe(DataExportRequest::STATUS_FAILED);
    expect(AuditLog::where('event_name', AuditEvent::DataExportFailed->value)->exists())->toBeTrue();
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
