<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Storage;

describe('GET /certificado/{code}', function () {
    it('returns pdf redirect for valid certificate code', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERT123',
            'present' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(true);
        $mockService->shouldReceive('getSignedUrl')
            ->once()
            ->andReturn('https://s3.example.com/certificate.pdf');

        $response = $this->get('/certificado/CERT123');

        $response->assertRedirect('https://s3.example.com/certificate.pdf');
    });

    it('generates pdf if not exists', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERT456',
            'present' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(false);
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldReceive('generatePdf')->once();
        $mockService->shouldReceive('getSignedUrl')
            ->once()
            ->andReturn('https://s3.example.com/certificate.pdf');

        $response = $this->get('/certificado/CERT456');

        $response->assertRedirect();
    });

    it('generates jpg and pdf if neither exists', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERT789',
            'present' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('pdfExists')->once()->andReturn(false);
        $mockService->shouldReceive('jpgExists')->once()->andReturn(false);
        $mockService->shouldReceive('generateJpg')->once();
        $mockService->shouldReceive('generatePdf')->once();
        $mockService->shouldReceive('getSignedUrl')
            ->once()
            ->andReturn('https://s3.example.com/certificate.pdf');

        $response = $this->get('/certificado/CERT789');

        $response->assertRedirect();
    });

    it('returns 404 for invalid certificate code', function () {
        $response = $this->get('/certificado/INVALID');

        $response->assertNotFound();
    });

    it('redirects to a signed URL whose content-disposition uses the seminar slug for the PDF route', function () {
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, array $options = []): string {
            return 'https://s3.example.test/'.ltrim($path, '/').'?response-content-disposition='.rawurlencode($options['ResponseContentDisposition'] ?? '');
        });

        $seminar = Seminar::factory()->create([
            'slug' => 'apresentacao-x',
            'scheduled_at' => '2026-05-01 10:00:00',
        ]);
        $registration = Registration::factory()->for($seminar)->create([
            'present' => true,
            'certificate_code' => 'cert-pdf-slug',
        ]);

        Storage::disk('s3')->put(
            "certificates/{$seminar->scheduled_at->year}/apresentacao-x/{$registration->certificate_code}.jpg",
            'jpg'
        );
        Storage::disk('s3')->put(
            "certificates/{$seminar->scheduled_at->year}/apresentacao-x/{$registration->certificate_code}.pdf",
            'pdf'
        );

        $response = $this->get("/certificado/{$registration->certificate_code}");

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        expect($target)->toContain('certificado-apresentacao-x.pdf');
    });
});

describe('GET /certificado/{code}/jpg', function () {
    it('returns jpg redirect for valid certificate code', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERTJPG1',
            'present' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
        $mockService->shouldReceive('getSignedUrl')
            ->once()
            ->andReturn('https://s3.example.com/certificate.jpg');

        $response = $this->get('/certificado/CERTJPG1/jpg');

        $response->assertRedirect('https://s3.example.com/certificate.jpg');
    });

    it('generates jpg if not exists', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERTJPG2',
            'present' => true,
        ]);

        $mockService = $this->mock(CertificateService::class);
        $mockService->shouldReceive('jpgExists')->once()->andReturn(false);
        $mockService->shouldReceive('generateJpg')->once();
        $mockService->shouldReceive('getSignedUrl')
            ->once()
            ->andReturn('https://s3.example.com/certificate.jpg');

        $response = $this->get('/certificado/CERTJPG2/jpg');

        $response->assertRedirect();
    });

    it('returns 404 for invalid certificate code', function () {
        $response = $this->get('/certificado/INVALID/jpg');

        $response->assertNotFound();
    });

    it('redirects to a signed URL whose content-disposition uses the seminar slug for the JPG route', function () {
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, array $options = []): string {
            return 'https://s3.example.test/'.ltrim($path, '/').'?response-content-disposition='.rawurlencode($options['ResponseContentDisposition'] ?? '');
        });

        $seminar = Seminar::factory()->create([
            'slug' => 'apresentacao-y',
            'scheduled_at' => '2026-05-01 10:00:00',
        ]);
        $registration = Registration::factory()->for($seminar)->create([
            'present' => true,
            'certificate_code' => 'cert-jpg-slug',
        ]);

        Storage::disk('s3')->put(
            "certificates/{$seminar->scheduled_at->year}/apresentacao-y/{$registration->certificate_code}.jpg",
            'jpg'
        );

        $response = $this->get("/certificado/{$registration->certificate_code}/jpg");

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        expect($target)->toContain('certificado-apresentacao-y.jpg');
    });
});
