<?php

use App\Console\Commands\CleanupOrphanSubjectsCommand;
use App\Jobs\GenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\Subject;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(WithConsoleEvents::class);

it('sets audit.origin to the job class when a job is processed via the sync queue', function () {
    Context::forget('audit.origin');
    Mail::fake();
    Notification::fake();
    Storage::fake('s3');

    $seminar = Seminar::factory()->create();
    $registration = Registration::factory()->create([
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => 'TEST123',
        'certificate_sent' => false,
    ]);

    $mockService = $this->mock(CertificateService::class);
    $mockService->shouldReceive('ensureCertificateCode')->once();
    $mockService->shouldReceive('jpgExists')->once()->andReturn(false);
    $mockService->shouldReceive('generateJpg')->once();
    $mockService->shouldReceive('pdfExists')->once()->andReturn(false);
    $mockService->shouldReceive('generatePdf')->once();
    $mockService->shouldReceive('getPdfPath')->once()->andReturn('certificates/test.pdf');

    Storage::disk('s3')->put('certificates/test.pdf', 'pdf content');

    // Dispatch via the queue so JobProcessing fires (SyncQueue dispatches it before handle()).
    GenerateCertificateJob::dispatch($registration);

    $row = AuditLog::query()
        ->where('event_name', 'certificate.generated')
        ->latest()
        ->first();

    expect($row)->not->toBeNull();
    expect($row->origin)->toBe(GenerateCertificateJob::class);
});

it('sets audit.origin to the command class when an artisan command runs', function () {
    Context::forget('audit.origin');

    Subject::factory()->create(); // orphan (no attached seminars)

    $this->artisan('subjects:cleanup-orphans')
        ->expectsConfirmation('Soft-delete 1 orphan subject(s)?', 'yes')
        ->assertExitCode(0);

    $row = AuditLog::query()
        ->where('event_name', 'command.orphan_subjects_cleaned_up')
        ->latest()
        ->first();

    expect($row)->not->toBeNull();
    expect($row->origin)->toBe(CleanupOrphanSubjectsCommand::class);
});
