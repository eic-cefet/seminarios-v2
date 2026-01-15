<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class CertificateService
{
    protected string $assetsPath;

    public function __construct()
    {
        $this->assetsPath = storage_path('app/assets/certificate');
    }

    public function getCertificatePath(Registration $registration): string
    {
        $seminar = $registration->seminar;
        $year = $seminar->scheduled_at->year;

        return "certificates/{$year}/{$seminar->slug}/{$registration->certificate_code}";
    }

    public function getJpgPath(Registration $registration): string
    {
        return $this->getCertificatePath($registration) . '.jpg';
    }

    public function getPdfPath(Registration $registration): string
    {
        return $this->getCertificatePath($registration) . '.pdf';
    }

    public function jpgExists(Registration $registration): bool
    {
        $cacheKey = "certificate_exists_jpg:{$registration->certificate_code}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($registration) {
            return Storage::disk('s3')->exists($this->getJpgPath($registration));
        });
    }

    public function pdfExists(Registration $registration): bool
    {
        $cacheKey = "certificate_exists_pdf:{$registration->certificate_code}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($registration) {
            return Storage::disk('s3')->exists($this->getPdfPath($registration));
        });
    }

    public function markJpgExists(Registration $registration): void
    {
        Cache::put("certificate_exists_jpg:{$registration->certificate_code}", true, now()->addDay());
    }

    public function markPdfExists(Registration $registration): void
    {
        Cache::put("certificate_exists_pdf:{$registration->certificate_code}", true, now()->addDay());
    }

    public function generateCertificateCode(): string
    {
        return Str::uuid()->toString();
    }

    public function ensureCertificateCode(Registration $registration): string
    {
        if (empty($registration->certificate_code)) {
            $registration->certificate_code = $this->generateCertificateCode();
            $registration->save();
        }

        return $registration->certificate_code;
    }

    public function generateJpg(Registration $registration): string
    {
        $registration->load(['user', 'seminar.seminarType']);

        $this->ensureCertificateCode($registration);

        $positions = [
            'header' => 215,
            'title' => 255,
            'student' => 315,
            'f_line' => 176,
            's_line' => 331,
            'seminary_date' => 395,
            'seminary' => 465,
            'watermark' => 580,
            'code_title' => 695,
            'code' => 715,
            'medal' => 565,
        ];

        $widths = [
            'code_title' => 840,
            'code' => 840,
            'watermark' => 125,
            'medal' => 785,
        ];

        $cp = $this->assetsPath;

        $certificate = Image::read($cp . '/Border.png')->resize(1100, 809);
        $centerW = $certificate->width() / 2;

        // Certificate Header
        $certificate->text('Certificado', $centerW, $positions['header'], function (FontFactory $font) use ($cp) {
            $font->filename($cp . '/cac_champagne.ttf');
            $font->size(120);
            $font->color('#222222');
            $font->align('center');
        });

        // Certificate Title
        $certificate->text(
            'A Escola de Informática e Computação do CEFET-RJ certifica que',
            $centerW,
            $positions['title'],
            function (FontFactory $font) use ($cp) {
                $font->filename($cp . '/Montserrat-Light.otf');
                $font->size(19);
                $font->color('#444444');
                $font->align('center');
            }
        );

        // Name lines
        $nameLine = Image::read($cp . '/NameLine.png')->resize(890, 271);
        $certificate->place($nameLine, 'top-center', 0, $positions['f_line']);
        $certificate->place($nameLine, 'top-center', 0, $positions['s_line']);

        // Medal
        $medal = Image::read($cp . '/Medal.png');
        $certificate->place($medal, 'top-left', $widths['medal'], $positions['medal']);

        // Watermark
        $watermark = Image::read($cp . '/Watermark.png')->resize(140, 95);
        $certificate->place($watermark, 'top-left', $widths['watermark'], $positions['watermark']);

        // Digital certificate text
        $certificate->text('Certificado digital', $widths['code_title'], $positions['code_title'], function (FontFactory $font) use ($cp) {
            $font->filename($cp . '/Montserrat-Regular.otf');
            $font->size(15);
            $font->color('#222222');
            $font->align('center');
        });

        // Student name
        $studentName = $this->formatName($registration->user->name);
        $fontSize = $this->calculateFontSize(strlen($studentName), [34 => 55, 42 => 46], 39);

        $certificate->text($studentName, $centerW, $positions['student'], function (FontFactory $font) use ($cp, $fontSize) {
            $font->filename($cp . '/cac_champagne.ttf');
            $font->size($fontSize);
            $font->color('#222222');
            $font->align('center');
        });

        // Seminary date and type
        $date = $registration->seminar->scheduled_at->format('d/m/Y') . ' às ' . $registration->seminar->scheduled_at->format('H:i');
        $typeName = $registration->seminar->seminarType->name;
        $typeDisplay = $typeName === mb_strtoupper($typeName) ? $typeName : mb_strtolower($typeName);

        $certificate->text(
            "Compareceu, no dia {$date}, ao {$typeDisplay}",
            $centerW,
            $positions['seminary_date'],
            function (FontFactory $font) use ($cp) {
                $font->filename($cp . '/Montserrat-Light.otf');
                $font->size(19);
                $font->color('#444444');
                $font->align('center');
            }
        );

        // Seminary name
        $seminarName = trim($registration->seminar->name);
        $seminarFontSize = $this->calculateFontSize(strlen($seminarName), [76 => 18, 82 => 17, 90 => 16, 103 => 14], 12);

        $certificate->text($seminarName, $centerW, $positions['seminary'], function (FontFactory $font) use ($cp, $seminarFontSize) {
            $font->filename($cp . '/Montserrat-Light.otf');
            $font->size($seminarFontSize);
            $font->color('#222222');
            $font->align('center');
        });

        // Certificate code
        $certificate->text($registration->certificate_code, $widths['code'], $positions['code'], function (FontFactory $font) use ($cp) {
            $font->filename($cp . '/Montserrat-Regular.otf');
            $font->size(13);
            $font->color('#222222');
            $font->align('center');
        });

        // Encode to JPG
        $jpgContent = $certificate->toJpeg(100)->toString();

        // Upload to S3
        Storage::disk('s3')->put($this->getJpgPath($registration), $jpgContent, 'private');
        $this->markJpgExists($registration);

        Log::info('JPG generated and uploaded to S3', [
            'registration_id' => $registration->id,
            'jpg_path' => $this->getJpgPath($registration),
        ]);

        return $this->getJpgPath($registration);
    }

    public function generatePdf(Registration $registration): string
    {
        $jpgPath = $this->getJpgPath($registration);

        if (!Storage::disk('s3')->exists($jpgPath)) {
            $this->generateJpg($registration);
        }

        $jpgContent = Storage::disk('s3')->get($jpgPath);

        // Create PDF with the JPG image
        $pdf = app('dompdf.wrapper');
        $base64Image = base64_encode($jpgContent);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0;
            size: 1100px 809px;
        }
        body {
            margin: 0;
            padding: 0;
        }
        img {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <img src="data:image/jpeg;base64,{$base64Image}" />
</body>
</html>
HTML;

        $pdf->loadHTML($html);
        $pdf->setPaper([0, 0, 1100, 809]);

        $pdfContent = $pdf->output();

        Storage::disk('s3')->put($this->getPdfPath($registration), $pdfContent, 'private');
        $this->markPdfExists($registration);

        Log::info('PDF generated and uploaded to S3', [
            'registration_id' => $registration->id,
            'pdf_path' => $this->getPdfPath($registration),
        ]);

        return $this->getPdfPath($registration);
    }

    public function getSignedUrl(Registration $registration, string $format = 'pdf'): string
    {
        $path = $format === 'pdf' ? $this->getPdfPath($registration) : $this->getJpgPath($registration);

        return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
    }

    protected function formatName(string $name): string
    {
        $words = collect(explode(' ', trim($name)));

        return $words->map(function ($word) {
            return collect(explode("'", $word))->map(function ($w) {
                return ucfirst(strtolower($w));
            })->implode("'");
        })->implode(' ');
    }

    protected function calculateFontSize(int $length, array $thresholds, int $default): int
    {
        foreach ($thresholds as $maxLength => $size) {
            if ($length <= $maxLength) {
                return $size;
            }
        }

        return $default;
    }
}
