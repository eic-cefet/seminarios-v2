<?php

use App\Models\Registration;
use App\Services\CertificateService;

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
});
