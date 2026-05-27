<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

it('returns a signed S3 URL that forces the download filename for the JPG', function () {
    $seminar = Seminar::factory()->create([
        'slug' => 'apresentacao-teste',
        'scheduled_at' => '2026-05-01 10:00:00',
    ]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    Storage::disk('s3')->put(
        "certificates/2026/apresentacao-teste/{$registration->certificate_code}.jpg",
        'fake-jpg-bytes'
    );

    $capturedOptions = [];
    Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, array $options = []) use (&$capturedOptions): string {
        $capturedOptions = $options;

        return 'https://s3.example.test/'.ltrim($path, '/').'?response-content-disposition='.rawurlencode($options['ResponseContentDisposition'] ?? '');
    });

    $service = app(CertificateService::class);
    $url = $service->getSignedUrl($registration, 'jpg');

    expect($capturedOptions)->toHaveKey('ResponseContentDisposition');
    expect($capturedOptions['ResponseContentDisposition'])->toBe(
        'attachment; filename="certificado-apresentacao-teste.jpg"'
    );
    expect($url)->toContain('response-content-disposition')
        ->and($url)->toContain('certificado-apresentacao-teste.jpg');
});

it('returns a signed S3 URL that forces the download filename for the PDF', function () {
    $seminar = Seminar::factory()->create([
        'slug' => 'apresentacao-teste',
        'scheduled_at' => '2026-05-01 10:00:00',
    ]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    Storage::disk('s3')->put(
        "certificates/2026/apresentacao-teste/{$registration->certificate_code}.pdf",
        'fake-pdf-bytes'
    );

    $capturedOptions = [];
    Storage::disk('s3')->buildTemporaryUrlsUsing(function ($path, $expiration, array $options = []) use (&$capturedOptions): string {
        $capturedOptions = $options;

        return 'https://s3.example.test/'.ltrim($path, '/').'?response-content-disposition='.rawurlencode($options['ResponseContentDisposition'] ?? '');
    });

    $service = app(CertificateService::class);
    $url = $service->getSignedUrl($registration, 'pdf');

    expect($capturedOptions)->toHaveKey('ResponseContentDisposition');
    expect($capturedOptions['ResponseContentDisposition'])->toBe(
        'attachment; filename="certificado-apresentacao-teste.pdf"'
    );
    expect($url)->toContain('response-content-disposition')
        ->and($url)->toContain('certificado-apresentacao-teste.pdf');
});
