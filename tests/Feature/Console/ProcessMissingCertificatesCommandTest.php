<?php

use App\Enums\AuditEvent;
use App\Jobs\GenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;

beforeEach(function () {
    Storage::fake('s3');
    Mail::fake();
});

it('repairs missing files synchronously despite stale positive cache entries', function (string $missing) {
    $registration = Registration::factory()->create([
        'present' => true,
        'certificate_code' => 'repair-code',
        'certificate_sent' => true,
    ]);
    if ($missing === 'pdf') {
        app(CertificateService::class)->generateJpg($registration);
    } elseif ($missing === 'jpg') {
        Storage::disk('s3')->put('certificates/repair-code.pdf', 'existing PDF');
    }
    $originalJpg = $missing === 'pdf' ? Storage::disk('s3')->get('certificates/repair-code.jpg') : null;
    Cache::put('certificate_exists_jpg:repair-code', true, 3600);
    Cache::put('certificate_exists_pdf:repair-code', true, 3600);

    $this->artisan('certificates:process-missing --sync')->assertSuccessful();

    Storage::disk('s3')->assertExists(['certificates/repair-code.jpg', 'certificates/repair-code.pdf']);
    if ($missing === 'jpg') {
        expect(Storage::disk('s3')->get('certificates/repair-code.pdf'))->toBe('existing PDF');
    } elseif ($missing === 'pdf') {
        expect(Storage::disk('s3')->get('certificates/repair-code.jpg') === $originalJpg)->toBeTrue();
    }
    expect($registration->fresh()->certificate_code)->toBe('repair-code');
    expect($registration->fresh()->certificate_sent)->toBeTrue();
    expect(AuditLog::where('event_name', AuditEvent::MissingCertificatesProcessed->value)->exists())->toBeTrue();
    Mail::assertNothingOutgoing();
})->with(['both', 'jpg', 'pdf']);

it('preserves existing files despite stale negative cache entries', function () {
    Registration::factory()->create(['present' => true, 'certificate_code' => 'existing-code']);
    foreach (['jpg', 'pdf'] as $format) {
        Storage::disk('s3')->put('certificates/existing-code.'.$format, 'original');
        Cache::put('certificate_exists_'.$format.':existing-code', false, 3600);
    }

    $this->artisan('certificates:process-missing --sync')->assertSuccessful();

    foreach (['jpg', 'pdf'] as $format) {
        expect(Storage::disk('s3')->get('certificates/existing-code.'.$format) === 'original')->toBeTrue();
    }
});

it('repairs only confirmed attendees in the selected seminar and creates missing codes', function () {
    $selected = Registration::factory()->create(['present' => true, 'certificate_code' => null]);
    $absent = Registration::factory()->create([
        'seminar_id' => $selected->seminar_id, 'present' => false, 'certificate_code' => null,
    ]);
    $other = Registration::factory()->create(['present' => true, 'certificate_code' => null]);

    $this->artisan('certificates:process-missing', ['--sync' => true, '--seminar' => $selected->seminar_id])
        ->assertSuccessful();

    $code = $selected->fresh()->certificate_code;
    expect($code)->not->toBeNull();
    Storage::disk('s3')->assertExists(['certificates/'.$code.'.jpg', 'certificates/'.$code.'.pdf']);
    expect($absent->fresh()->certificate_code)->toBeNull()
        ->and($other->fresh()->certificate_code)->toBeNull();
});

it('reports a failed storage check and continues repairing subsequent registrations', function () {
    Registration::factory()->create(['present' => true, 'certificate_code' => 'inaccessible']);
    Registration::factory()->create(['present' => true, 'certificate_code' => 'repairable']);
    $disk = Mockery::mock(Storage::disk('s3'))->makePartial();
    $disk->shouldReceive('fileExists')->with('certificates/inaccessible.jpg')
        ->andThrow(UnableToCheckFileExistence::forLocation('certificates/inaccessible.jpg'));
    Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

    $this->artisan('certificates:process-missing --sync')->assertFailed();

    $disk->assertMissing(['certificates/inaccessible.jpg', 'certificates/inaccessible.pdf']);
    $disk->assertExists(['certificates/repairable.jpg', 'certificates/repairable.pdf']);
});

it('queues missing certificates using fresh checks when sync is omitted', function () {
    Queue::fake();
    $registration = Registration::factory()->create(['present' => true, 'certificate_code' => 'queued-code']);
    Cache::put('certificate_exists_jpg:queued-code', true, 3600);
    Cache::put('certificate_exists_pdf:queued-code', true, 3600);

    $this->artisan('certificates:process-missing')->assertSuccessful();

    Queue::assertPushed(GenerateCertificateJob::class, fn ($job) => $job->registration->is($registration) && ! $job->sendEmail);
    expect(Cache::get('certificate_exists_jpg:queued-code'))->toBeFalse()
        ->and(Cache::get('certificate_exists_pdf:queued-code'))->toBeFalse();
});

it('succeeds when no confirmed attendees need scanning', function () {
    $this->artisan('certificates:process-missing --sync')->assertSuccessful();
});
