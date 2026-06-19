<?php

use App\Jobs\RegenerateCertificateJob;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();
    // The --sync path invokes RegenerateCertificateJob::handle(), which renders a
    // real JPG/PDF to the s3 disk — fake it so no network/storage is touched.
    Storage::fake('s3');
});

function typedReg(string $typeName = 'Seminário', string $scheduledAt = '2026-05-20 10:00:00'): Registration
{
    $type = SeminarType::factory()->create(['name' => $typeName]);
    $seminar = Seminar::factory()->create([
        'seminar_type_id' => $type->id,
        'scheduled_at' => $scheduledAt,
    ]);

    return Registration::factory()->create([
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => (string) Str::uuid(),
    ]);
}

it('dispatches a regenerate job for each typed, issued certificate', function () {
    $reg = typedReg();

    $this->artisan('certificates:reprocess')->assertSuccessful();

    Queue::assertPushed(RegenerateCertificateJob::class, 1);
    Queue::assertPushed(RegenerateCertificateJob::class,
        fn (RegenerateCertificateJob $job) => $job->registration->is($reg));
});

it('excludes untyped seminars', function () {
    $seminar = Seminar::factory()->create(['seminar_type_id' => null]);
    Registration::factory()->create([
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => (string) Str::uuid(),
    ]);

    $this->artisan('certificates:reprocess')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('excludes registrations without a certificate code or presence', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create(['seminar_type_id' => $type->id]);
    Registration::factory()->create([
        'seminar_id' => $seminar->id, 'present' => true, 'certificate_code' => null,
    ]);
    Registration::factory()->create([
        'seminar_id' => $seminar->id, 'present' => false, 'certificate_code' => (string) Str::uuid(),
    ]);

    $this->artisan('certificates:reprocess')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('filters by --since on the seminar scheduled date', function () {
    $old = typedReg('Seminário', '2026-05-01 10:00:00');
    $new = typedReg('Seminário', '2026-05-20 10:00:00');

    $this->artisan('certificates:reprocess', ['--since' => '2026-05-09'])->assertSuccessful();

    Queue::assertPushed(RegenerateCertificateJob::class, 1);
    Queue::assertPushed(RegenerateCertificateJob::class,
        fn (RegenerateCertificateJob $job) => $job->registration->is($new));
});

it('filters by --type name', function () {
    $sem = typedReg('Seminário');
    $diss = typedReg('Dissertação');

    $this->artisan('certificates:reprocess', ['--type' => 'Dissertação'])->assertSuccessful();

    Queue::assertPushed(RegenerateCertificateJob::class, 1);
    Queue::assertPushed(RegenerateCertificateJob::class,
        fn (RegenerateCertificateJob $job) => $job->registration->is($diss));
});

it('filters by --seminar id', function () {
    $a = typedReg();
    $b = typedReg();

    $this->artisan('certificates:reprocess', ['--seminar' => (string) $a->seminar_id])->assertSuccessful();

    Queue::assertPushed(RegenerateCertificateJob::class, 1);
    Queue::assertPushed(RegenerateCertificateJob::class,
        fn (RegenerateCertificateJob $job) => $job->registration->is($a));
});

it('filters by --seminar slug', function () {
    $a = typedReg();
    Seminar::whereKey($a->seminar_id)->update(['slug' => 'minha-apresentacao']);

    $this->artisan('certificates:reprocess', ['--seminar' => 'minha-apresentacao'])->assertSuccessful();

    Queue::assertPushed(RegenerateCertificateJob::class, 1);
});

it('dispatches nothing on --dry-run but reports the count', function () {
    typedReg();

    $this->artisan('certificates:reprocess', ['--dry-run' => true])
        ->expectsOutputToContain('1 certificate(s) would be reprocessed')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('runs jobs inline with --sync', function () {
    typedReg();

    // With Queue::fake(), --sync should NOT enqueue; it invokes handle() directly.
    $this->artisan('certificates:reprocess', ['--sync' => true])->assertSuccessful();

    Queue::assertNothingPushed();
});

it('reports when nothing matches', function () {
    $this->artisan('certificates:reprocess')
        ->expectsOutputToContain('No certificates')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('continues past a sync failure and reports it', function () {
    typedReg();

    $this->mock(CertificateService::class)
        ->shouldReceive('generateJpg')
        ->andThrow(new RuntimeException('render boom'));

    $this->artisan('certificates:reprocess', ['--sync' => true])
        ->expectsOutputToContain('Failed to reprocess')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('logs lifecycle events with the matched count and dispatch result', function () {
    Log::spy();
    $reg = typedReg();

    $this->artisan('certificates:reprocess')->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'started')
            && $context['mode'] === 'queued')
        ->once();
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'matched certificates')
            && $context['count'] === 1)
        ->once();
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'dispatched regenerate job')
            && $context['registration_id'] === $reg->id)
        ->once();
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'completed')
            && $context['dispatched'] === 1
            && $context['failed'] === 0)
        ->once();
});
