<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    Cache::flush();
});

describe('CertificateService path methods', function () {
    it('generates correct certificate path', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'test-seminar',
            'scheduled_at' => '2024-06-15 10:00:00',
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'test-certificate-code-123',
        ]);

        $service = new CertificateService;
        $path = $service->getCertificatePath($registration);

        expect($path)->toBe('certificates/2024/test-seminar/test-certificate-code-123');
    });

    it('generates correct JPG path', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'test-seminar',
            'scheduled_at' => '2024-06-15 10:00:00',
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'test-code',
        ]);

        $service = new CertificateService;
        $path = $service->getJpgPath($registration);

        expect($path)->toBe('certificates/2024/test-seminar/test-code.jpg');
    });

    it('generates correct PDF path', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'test-seminar',
            'scheduled_at' => '2024-06-15 10:00:00',
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'test-code',
        ]);

        $service = new CertificateService;
        $path = $service->getPdfPath($registration);

        expect($path)->toBe('certificates/2024/test-seminar/test-code.pdf');
    });
});

describe('CertificateService code generation', function () {
    it('generates unique certificate codes', function () {
        $service = new CertificateService;

        $codes = collect(range(1, 10))->map(fn () => $service->generateCertificateCode());

        // All codes should be unique
        expect($codes->unique()->count())->toBe(10);

        // All codes should be valid UUIDs
        $codes->each(function ($code) {
            expect($code)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
        });
    });

    it('ensures certificate code creates new code when missing', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => null,
        ]);

        $service = new CertificateService;
        $code = $service->ensureCertificateCode($registration);

        expect($code)
            ->toBeString()
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

        // Should be saved to database
        expect($registration->fresh()->certificate_code)->toBe($code);
    });

    it('ensures certificate code returns existing code when present', function () {
        $existingCode = 'existing-code-12345';

        $registration = Registration::factory()->create([
            'certificate_code' => $existingCode,
        ]);

        $service = new CertificateService;
        $code = $service->ensureCertificateCode($registration);

        expect($code)->toBe($existingCode);
    });
});

describe('CertificateService existence checks', function () {
    it('checks if JPG exists and caches result', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'test-seminar',
            'scheduled_at' => '2024-01-15 10:00:00',
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'cached-code',
        ]);

        $service = new CertificateService;

        // File doesn't exist
        expect($service->jpgExists($registration))->toBeFalse();

        // Create the file
        Storage::disk('s3')->put('certificates/2024/test-seminar/cached-code.jpg', 'fake-content');
        Cache::flush(); // Clear cache to check again

        expect($service->jpgExists($registration))->toBeTrue();
    });

    it('checks if PDF exists and caches result', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'test-seminar',
            'scheduled_at' => '2024-01-15 10:00:00',
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'cached-code',
        ]);

        $service = new CertificateService;

        // File doesn't exist
        expect($service->pdfExists($registration))->toBeFalse();

        // Create the file
        Storage::disk('s3')->put('certificates/2024/test-seminar/cached-code.pdf', 'fake-content');
        Cache::flush();

        expect($service->pdfExists($registration))->toBeTrue();
    });

    it('marks JPG as existing in cache', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'mark-test-code',
        ]);

        $service = new CertificateService;
        $service->markJpgExists($registration);

        expect(Cache::get('certificate_exists_jpg:mark-test-code'))->toBeTrue();
    });

    it('marks PDF as existing in cache', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'mark-test-code',
        ]);

        $service = new CertificateService;
        $service->markPdfExists($registration);

        expect(Cache::get('certificate_exists_pdf:mark-test-code'))->toBeTrue();
    });
});

describe('CertificateService formatName helper', function () {
    it('formats ASCII names correctly', function () {
        $service = new CertificateService;

        // Use reflection to access protected method
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('formatName');
        $method->setAccessible(true);

        expect($method->invoke($service, 'JOHN DOE'))->toBe('John Doe');
        expect($method->invoke($service, 'john doe'))->toBe('John Doe');
        expect($method->invoke($service, "O'CONNOR"))->toBe("O'Connor");
    });

    it('handles apostrophes in names', function () {
        $service = new CertificateService;

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('formatName');
        $method->setAccessible(true);

        expect($method->invoke($service, "MARY O'BRIEN"))->toBe("Mary O'Brien");
        expect($method->invoke($service, "D'ARTAGNAN"))->toBe("D'Artagnan");
    });
});

describe('CertificateService calculateFontSize helper', function () {
    it('calculates correct font sizes based on thresholds', function () {
        $service = new CertificateService;

        // Use reflection to access protected method
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateFontSize');
        $method->setAccessible(true);

        $thresholds = [34 => 55, 42 => 46];

        // Under first threshold
        expect($method->invoke($service, 30, $thresholds, 39))->toBe(55);

        // At first threshold
        expect($method->invoke($service, 34, $thresholds, 39))->toBe(55);

        // Between first and second threshold
        expect($method->invoke($service, 38, $thresholds, 39))->toBe(46);

        // At second threshold
        expect($method->invoke($service, 42, $thresholds, 39))->toBe(46);

        // Above all thresholds - should return default
        expect($method->invoke($service, 50, $thresholds, 39))->toBe(39);
    });
});
