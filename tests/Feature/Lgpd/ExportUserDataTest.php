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
