<?php

use App\Jobs\GenerateCertificateJob;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

describe('certificates:process-pending command', function () {
    it('outputs no pending certificates when there are none', function () {
        $this->artisan('certificates:process-pending')
            ->expectsOutput('Finding registrations with pending certificates...')
            ->expectsOutput('No pending certificates to process.')
            ->assertExitCode(0);
    });

    it('finds registrations with present=true and certificate_sent=false', function () {
        Queue::fake();

        $seminar = Seminar::factory()->past()->create();
        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_sent' => false,
            'certificate_code' => null,
        ]);

        $this->artisan('certificates:process-pending')
            ->expectsOutput('Finding registrations with pending certificates...')
            ->expectsOutputToContain('Found 1 registration(s) with pending certificates.')
            ->expectsOutputToContain($user->email)
            ->expectsOutput('Dispatched 1 certificate job(s).')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateCertificateJob::class, 1);
    });

    it('finds registrations with present=true and certificate_code=null', function () {
        Queue::fake();

        $seminar = Seminar::factory()->past()->create();
        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_sent' => true,
            'certificate_code' => null,
        ]);

        $this->artisan('certificates:process-pending')
            ->expectsOutputToContain('Found 1 registration(s)')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateCertificateJob::class);
    });

    it('ignores registrations where user was not present', function () {
        Queue::fake();

        Registration::factory()->create([
            'present' => false,
            'certificate_sent' => false,
        ]);

        $this->artisan('certificates:process-pending')
            ->expectsOutput('No pending certificates to process.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores registrations that already have certificate sent and code', function () {
        Queue::fake();

        Registration::factory()->create([
            'present' => true,
            'certificate_sent' => true,
            'certificate_code' => 'ABC123',
        ]);

        $this->artisan('certificates:process-pending')
            ->expectsOutput('No pending certificates to process.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('skips registrations without user', function () {
        Queue::fake();

        $seminar = Seminar::factory()->past()->create();

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_sent' => false,
        ]);

        // Delete user to simulate orphaned registration
        $registration->user->delete();

        $this->artisan('certificates:process-pending')
            ->expectsOutputToContain('Found 1 registration(s)')
            ->expectsOutput('Dispatched 0 certificate job(s).')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('skips registrations without seminar', function () {
        Queue::fake();

        $user = User::factory()->create();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'present' => true,
            'certificate_sent' => false,
        ]);

        // Delete seminar to simulate orphaned registration
        $registration->seminar->delete();

        $this->artisan('certificates:process-pending')
            ->expectsOutputToContain('Found 1 registration(s)')
            ->expectsOutput('Dispatched 0 certificate job(s).')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('dispatches job with sendEmail=true by default', function () {
        Queue::fake();

        Registration::factory()->present()->create([
            'certificate_sent' => false,
        ]);

        $this->artisan('certificates:process-pending')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateCertificateJob::class, function ($job) {
            return $job->sendEmail === true;
        });
    });

    it('dispatches job with sendEmail=false when --no-email is provided', function () {
        Queue::fake();

        Registration::factory()->present()->create([
            'certificate_sent' => false,
        ]);

        $this->artisan('certificates:process-pending --no-email')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateCertificateJob::class, function ($job) {
            return $job->sendEmail === false;
        });
    });

    it('processes multiple registrations', function () {
        Queue::fake();

        Registration::factory()->count(3)->present()->create([
            'certificate_sent' => false,
        ]);

        $this->artisan('certificates:process-pending')
            ->expectsOutputToContain('Found 3 registration(s)')
            ->expectsOutput('Dispatched 3 certificate job(s).')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateCertificateJob::class, 3);
    });

    it('processes synchronously with --sync option', function () {
        Queue::fake();
        Mail::fake();

        $registration = Registration::factory()->present()->create([
            'certificate_sent' => false,
            'certificate_code' => null,
        ]);

        // Mock the CertificateService with shouldIgnoreMissing to handle all methods
        $mockCertificateService = Mockery::mock(\App\Services\CertificateService::class)->shouldIgnoreMissing();
        $mockCertificateService->shouldReceive('ensureCertificateCode')
            ->andReturn('CERT-123');
        $mockCertificateService->shouldReceive('jpgExists')
            ->andReturn(true);
        $mockCertificateService->shouldReceive('pdfExists')
            ->andReturn(true);

        $this->app->instance(\App\Services\CertificateService::class, $mockCertificateService);

        // Use --no-email to avoid S3 storage operations
        $this->artisan('certificates:process-pending --sync --no-email')
            ->expectsOutputToContain('Found 1 registration(s)')
            ->expectsOutput('Dispatched 1 certificate job(s).')
            ->assertExitCode(0);

        // Job should NOT be queued when using --sync
        Queue::assertNothingPushed();
    });

});
