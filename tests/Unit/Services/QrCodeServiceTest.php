<?php

use App\Services\QrCodeOutputFormat;
use App\Services\QrCodeService;

describe('QrCodeService', function () {
    it('generates a base64 QR code by default', function () {
        $service = new QrCodeService;

        $result = $service->generate('https://example.com');

        expect($result)
            ->toBeString()
            ->toStartWith('data:image/png;base64,');
    });

    it('generates a base64 QR code when format is Base64', function () {
        $service = new QrCodeService;

        $result = $service->generate('https://example.com', QrCodeOutputFormat::Base64);

        expect($result)
            ->toBeString()
            ->toStartWith('data:image/png;base64,');
    });

    it('generates raw PNG binary when format is Png', function () {
        $service = new QrCodeService;

        $result = $service->generate('https://example.com', QrCodeOutputFormat::Png);

        expect($result)
            ->toBeString()
            // PNG files start with these bytes
            ->and(substr($result, 0, 4))->toBe(chr(137).'PNG');
    });

    it('uses the toBase64 convenience method correctly', function () {
        $service = new QrCodeService;

        $result = $service->toBase64('https://example.com');

        expect($result)
            ->toBeString()
            ->toStartWith('data:image/png;base64,');
    });

    it('uses the toPng convenience method correctly', function () {
        $service = new QrCodeService;

        $result = $service->toPng('https://example.com');

        expect($result)
            ->toBeString()
            ->and(substr($result, 0, 4))->toBe(chr(137).'PNG');
    });

    it('accepts custom scale parameter', function () {
        $service = new QrCodeService(scale: 4);

        $resultSmall = $service->toPng('test');

        $serviceLarge = new QrCodeService(scale: 16);
        $resultLarge = $serviceLarge->toPng('test');

        // Larger scale should produce larger image
        expect(strlen($resultLarge))->toBeGreaterThan(strlen($resultSmall));
    });

    it('accepts scale override in generate method', function () {
        $service = new QrCodeService(scale: 4);

        $resultSmall = $service->toPng('test', scale: 4);
        $resultLarge = $service->toPng('test', scale: 16);

        expect(strlen($resultLarge))->toBeGreaterThan(strlen($resultSmall));
    });

    it('encodes different data correctly', function () {
        $service = new QrCodeService;

        $result1 = $service->toBase64('data1');
        $result2 = $service->toBase64('data2');

        expect($result1)->not->toBe($result2);
    });

    it('produces valid base64 that can be decoded', function () {
        $service = new QrCodeService;

        $result = $service->toBase64('https://example.com');

        // Extract base64 data from data URI
        $base64Data = str_replace('data:image/png;base64,', '', $result);
        $decoded = base64_decode($base64Data, strict: true);

        expect($decoded)->not->toBeFalse()
            ->and(substr($decoded, 0, 4))->toBe(chr(137).'PNG');
    });
});
