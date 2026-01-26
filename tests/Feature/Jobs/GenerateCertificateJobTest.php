<?php

use App\Jobs\GenerateCertificateJob;
use App\Mail\CertificateGenerated;
use App\Models\Registration;
use App\Models\Seminar;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

describe('GenerateCertificateJob', function () {
    it('skips if registration is not present', function () {
        $registration = Registration::factory()->create([
            'present' => false,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldNotReceive('ensureCertificateCode');
        $mockService->shouldNotReceive('generateJpg');
        $mockService->shouldNotReceive('generatePdf');

        $job = new GenerateCertificateJob($registration);
        $job->handle($mockService);
    });

    it('generates jpg and pdf when neither exists', function () {
        Mail::fake();
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

        $job = new GenerateCertificateJob($registration);
        $job->handle($mockService);

        Mail::assertSent(CertificateGenerated::class);
        expect($registration->fresh()->certificate_sent)->toBeTrue();
    });

    it('skips jpg generation if already exists', function () {
        Mail::fake();
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
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldNotReceive('generateJpg');
        $mockService->shouldReceive('pdfExists')->once()->andReturn(false);
        $mockService->shouldReceive('generatePdf')->once();
        $mockService->shouldReceive('getPdfPath')->once()->andReturn('certificates/test.pdf');

        Storage::disk('s3')->put('certificates/test.pdf', 'pdf content');

        $job = new GenerateCertificateJob($registration);
        $job->handle($mockService);
    });

    it('skips pdf generation if already exists', function () {
        Mail::fake();
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
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(true);
        $mockService->shouldNotReceive('generateJpg');
        $mockService->shouldNotReceive('generatePdf');
        $mockService->shouldReceive('getPdfPath')->once()->andReturn('certificates/test.pdf');

        Storage::disk('s3')->put('certificates/test.pdf', 'pdf content');

        $job = new GenerateCertificateJob($registration);
        $job->handle($mockService);
    });

    it('skips email when sendEmail is false', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create();
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_code' => 'TEST123',
            'certificate_sent' => false,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('ensureCertificateCode')->once();
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(true);

        $job = new GenerateCertificateJob($registration, sendEmail: false);
        $job->handle($mockService);

        Mail::assertNothingSent();
        expect($registration->fresh()->certificate_sent)->toBeFalse();
    });

    it('skips email when certificate already sent', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create();
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_code' => 'TEST123',
            'certificate_sent' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('ensureCertificateCode')->once();
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(true);

        $job = new GenerateCertificateJob($registration);
        $job->handle($mockService);

        Mail::assertNothingSent();
    });
});
