<?php

use App\Jobs\DeleteS3FileJob;
use App\Jobs\GenerateAuditLogReportJob;
use App\Mail\ReportReady;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

it('generates audit log excel and sends email to recipient', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create(['email' => 'admin@example.com']);
    AuditLog::factory()->count(3)->create(['user_id' => $user->id]);

    $job = new GenerateAuditLogReportJob($user, days: 30);
    $job->handle();

    Mail::assertSent(ReportReady::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && str_contains($mail->reportName, '30');
    });

    Queue::assertPushed(DeleteS3FileJob::class);
});

it('applies event_type filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    AuditLog::factory()->manual()->count(2)->create(['user_id' => $user->id]);
    AuditLog::factory()->count(3)->create(['user_id' => $user->id]);

    $job = new GenerateAuditLogReportJob($user, days: 30, eventType: 'manual');
    $job->handle();

    Mail::assertSent(ReportReady::class);
});

it('applies event_name filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();
    AuditLog::factory()->create(['user_id' => $user->id, 'event_name' => 'user.login']);
    AuditLog::factory()->create(['user_id' => $user->id, 'event_name' => 'user.logout']);

    $job = new GenerateAuditLogReportJob($user, days: 30, eventName: 'user.login');
    $job->handle();

    Mail::assertSent(ReportReady::class);
});

it('applies search filter', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $searcher = User::factory()->create(['name' => 'Jorge Junior']);
    $other = User::factory()->create(['name' => 'Maria Silva']);
    AuditLog::factory()->create(['user_id' => $searcher->id]);
    AuditLog::factory()->create(['user_id' => $other->id]);

    $job = new GenerateAuditLogReportJob($searcher, days: 30, search: 'Jorge');
    $job->handle();

    Mail::assertSent(ReportReady::class);
});

it('throws and does not send email when s3 upload fails', function () {
    Storage::fake('s3');
    Excel::fake(); // faking Excel prevents the file from being written to storage
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();

    $job = new GenerateAuditLogReportJob($user, days: 30);

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);

    Mail::assertNothingSent();
});

it('records audit log on completion', function () {
    Storage::fake('s3');
    Mail::fake();
    Queue::fake();

    $user = User::factory()->create();

    $job = new GenerateAuditLogReportJob($user, days: 30);
    $job->handle();

    $this->assertDatabaseHas('audit_logs', [
        'event_name' => 'report.generated',
        'event_type' => 'system',
    ]);
});
