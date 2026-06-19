<?php

use App\Enums\AuditEvent;
use App\Jobs\RegenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

describe('RegenerateCertificateJob', function () {
    it('skips when registration is not present', function () {
        $registration = Registration::factory()->create([
            'present' => false,
            'certificate_code' => 'CODE-123',
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldNotReceive('generateJpg');
        $service->shouldNotReceive('generatePdf');

        (new RegenerateCertificateJob($registration))->handle($service);
    });

    it('skips when certificate_code is missing', function () {
        $registration = Registration::factory()->create([
            'present' => true,
            'certificate_code' => null,
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldNotReceive('generateJpg');
        $service->shouldNotReceive('generatePdf');

        (new RegenerateCertificateJob($registration))->handle($service);
    });

    it('regenerates JPG and PDF at the same path without mailing or notifying', function () {
        Mail::fake();
        Notification::fake();
        Storage::fake('s3');

        $registration = Registration::factory()->create([
            'present' => true,
            'certificate_code' => 'CODE-RG-1',
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldReceive('generateJpg')->once()->with(Mockery::on(
            fn ($r) => $r->is($registration)
        ));
        $service->shouldReceive('generatePdf')->once()->with(Mockery::on(
            fn ($r) => $r->is($registration)
        ));

        (new RegenerateCertificateJob($registration))->handle($service);

        Mail::assertNothingQueued();
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    });

    it('records a CertificateRegenerated audit entry', function () {
        Storage::fake('s3');

        $registration = Registration::factory()->create([
            'present' => true,
            'certificate_code' => 'CODE-RG-2',
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldReceive('generateJpg')->once();
        $service->shouldReceive('generatePdf')->once();

        (new RegenerateCertificateJob($registration))->handle($service);

        expect(AuditLog::query()
            ->where('event_name', AuditEvent::CertificateRegenerated->value)
            ->where('auditable_id', $registration->id)
            ->exists())->toBeTrue();
    });

    it('uses a unique job id scoped to the registration', function () {
        $registration = Registration::factory()->create();
        $job = new RegenerateCertificateJob($registration);

        expect($job->uniqueId())->toBe('regenerate-'.$registration->id);
    });

    it('logs start and completion of a successful regeneration', function () {
        Log::spy();
        Storage::fake('s3');

        $registration = Registration::factory()->create([
            'present' => true,
            'certificate_code' => 'CODE-RG-LOG',
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldReceive('generateJpg')->once()->andReturn('certificates/2026/sem/CODE-RG-LOG.jpg');
        $service->shouldReceive('generatePdf')->once()->andReturn('certificates/2026/sem/CODE-RG-LOG.pdf');

        (new RegenerateCertificateJob($registration))->handle($service);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'starting')
                && $context['registration_id'] === $registration->id)
            ->once();
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'regenerated certificate artefacts')
                && $context['jpg_path'] === 'certificates/2026/sem/CODE-RG-LOG.jpg'
                && $context['pdf_path'] === 'certificates/2026/sem/CODE-RG-LOG.pdf')
            ->once();
    });

    it('logs a warning when it skips a registration', function () {
        Log::spy();

        $registration = Registration::factory()->create([
            'present' => false,
            'certificate_code' => 'CODE-SKIP',
        ]);

        $service = $this->mock(CertificateService::class);
        $service->shouldNotReceive('generateJpg');

        (new RegenerateCertificateJob($registration))->handle($service);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'skipped')
                && $context['registration_id'] === $registration->id)
            ->once();
    });

    it('logs a permanent failure via failed()', function () {
        Log::spy();

        $registration = Registration::factory()->create([
            'present' => true,
            'certificate_code' => 'CODE-FAIL',
        ]);

        (new RegenerateCertificateJob($registration))->failed(new RuntimeException('render boom'));

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'failed permanently')
                && $context['registration_id'] === $registration->id
                && $context['exception'] === 'render boom')
            ->once();
    });
});
