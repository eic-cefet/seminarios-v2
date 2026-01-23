<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

enum QrCodeOutputFormat
{
    case Base64;
    case Png;
}

class QrCodeService
{
    public function __construct(
        protected int $scale = 8,
        protected int $eccLevel = QRCode::ECC_L,
    ) {}

    /**
     * Generate a QR code for the given data.
     *
     * @param  string  $data  The data to encode in the QR code
     * @param  QrCodeOutputFormat  $format  The output format (Base64 or Png)
     * @param  int|null  $scale  Optional scale override
     * @return string The QR code as base64 data URI or raw PNG binary
     */
    public function generate(
        string $data,
        QrCodeOutputFormat $format = QrCodeOutputFormat::Base64,
        ?int $scale = null,
    ): string {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => $this->eccLevel,
            'scale' => $scale ?? $this->scale,
            'outputBase64' => $format === QrCodeOutputFormat::Base64,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    /**
     * Generate a QR code as a base64 data URI.
     */
    public function toBase64(string $data, ?int $scale = null): string
    {
        return $this->generate($data, QrCodeOutputFormat::Base64, $scale);
    }

    /**
     * Generate a QR code as raw PNG binary data.
     */
    public function toPng(string $data, ?int $scale = null): string
    {
        return $this->generate($data, QrCodeOutputFormat::Png, $scale);
    }
}
