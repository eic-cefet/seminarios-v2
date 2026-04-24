<?php

use App\Jobs\GenerateCertificateJob;
use App\Mail\CertificateGenerated;
use App\Models\AlertPreference;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('s3');
});

it('does not send certificate email when user opted out of certificate_ready', function () {
    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['new_seminar_alert' => false, 'certificate_ready' => false],
    );

    $seminar = Seminar::factory()->create();
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => 'TEST123',
        'certificate_sent' => false,
    ]);

    $mockService = $this->mock(CertificateService::class);
    $mockService->shouldReceive('ensureCertificateCode')->once();
    $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
    $mockService->shouldReceive('pdfExists')->once()->andReturn(true);
    $mockService->shouldNotReceive('getPdfPath');

    (new GenerateCertificateJob($registration))->handle($mockService);

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    expect($registration->fresh()->certificate_sent)->toBeFalse();
});

it('still generates artifacts for opted-out users (only email is skipped)', function () {
    $user = User::factory()->create();
    AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        ['new_seminar_alert' => false, 'certificate_ready' => false],
    );

    $seminar = Seminar::factory()->create();
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
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

    (new GenerateCertificateJob($registration))->handle($mockService);

    Mail::assertNothingSent();
});

it('sends certificate email when user has no preferences row', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();

    $seminar = Seminar::factory()->create();
    $registration = Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => 'TEST123',
        'certificate_sent' => false,
    ]);

    $mockService = $this->mock(CertificateService::class);
    $mockService->shouldReceive('ensureCertificateCode')->once();
    $mockService->shouldReceive('jpgExists')->once()->andReturn(true);
    $mockService->shouldReceive('pdfExists')->once()->andReturn(true);
    $mockService->shouldReceive('getPdfPath')->once()->andReturn('certificates/test.pdf');

    Storage::disk('s3')->put('certificates/test.pdf', 'pdf content');

    (new GenerateCertificateJob($registration))->handle($mockService);

    Mail::assertSent(CertificateGenerated::class);
    expect($registration->fresh()->certificate_sent)->toBeTrue();
});
