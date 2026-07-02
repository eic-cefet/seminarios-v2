<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
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
        "certificates/{$registration->certificate_code}.jpg",
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
        "certificates/{$registration->certificate_code}.pdf",
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

it('generates a JPG for a typed seminar', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create([
        'slug' => 'sem-tipado',
        'scheduled_at' => '2026-05-20 10:00:00',
        'seminar_type_id' => $type->id,
    ]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    $path = app(CertificateService::class)->generateJpg($registration);

    Storage::disk('s3')->assertExists($path);
    expect($path)->toEndWith('.jpg');
});

it('generates a JPG for a seminar with no type (neutral fallback)', function () {
    $seminar = Seminar::factory()->create([
        'slug' => 'sem-sem-tipo',
        'scheduled_at' => '2026-05-20 10:00:00',
        'seminar_type_id' => null,
    ]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    $path = app(CertificateService::class)->generateJpg($registration);

    Storage::disk('s3')->assertExists($path);
    expect($path)->toEndWith('.jpg');
});

it('stores new certificates under the immutable code-keyed path', function () {
    Storage::fake('s3');

    $registration = Registration::factory()->create([
        'present' => true,
        'certificate_code' => 'test-code-immutable',
    ]);

    $service = app(CertificateService::class);

    expect($service->getJpgPath($registration))->toBe('certificates/test-code-immutable.jpg')
        ->and($service->getPdfPath($registration))->toBe('certificates/test-code-immutable.pdf');
});

it('lazily moves a legacy-path certificate to the immutable path on existence check', function () {
    Storage::fake('s3');

    $seminar = Seminar::factory()->create([
        'name' => 'Apresentação Teste',
        'slug' => 'apresentacao-teste',
        'scheduled_at' => now()->setYear(2026),
    ]);
    $registration = Registration::factory()->create([
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => 'legacy-code',
    ]);

    Storage::disk('s3')->put('certificates/2026/apresentacao-teste/legacy-code.jpg', 'jpg-bytes');

    $service = app(CertificateService::class);

    expect($service->jpgExists($registration))->toBeTrue();
    Storage::disk('s3')->assertExists('certificates/legacy-code.jpg');
    Storage::disk('s3')->assertMissing('certificates/2026/apresentacao-teste/legacy-code.jpg');
});

it('reports missing when the certificate exists at neither path', function () {
    Storage::fake('s3');

    $registration = Registration::factory()->create([
        'present' => true,
        'certificate_code' => 'nowhere-code',
    ]);

    expect(app(CertificateService::class)->pdfExists($registration))->toBeFalse();
});
