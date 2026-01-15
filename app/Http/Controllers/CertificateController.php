<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    public function show(string $code): RedirectResponse
    {
        $registration = Registration::where('certificate_code', $code)
            ->with(['seminar', 'user'])
            ->firstOrFail();

        if (!$this->certificateService->pdfExists($registration)) {
            if (!$this->certificateService->jpgExists($registration)) {
                $this->certificateService->generateJpg($registration);
            }
            $this->certificateService->generatePdf($registration);
        }

        $signedUrl = $this->certificateService->getSignedUrl($registration, 'pdf');

        return redirect()->away($signedUrl);
    }

    public function showJpg(string $code): RedirectResponse
    {
        $registration = Registration::where('certificate_code', $code)
            ->with(['seminar', 'user'])
            ->firstOrFail();

        if (!$this->certificateService->jpgExists($registration)) {
            $this->certificateService->generateJpg($registration);
        }

        $signedUrl = $this->certificateService->getSignedUrl($registration, 'jpg');

        return redirect()->away($signedUrl);
    }
}
