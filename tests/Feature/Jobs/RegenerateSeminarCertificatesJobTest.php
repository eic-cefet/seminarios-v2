<?php

use App\Jobs\RegenerateCertificateJob;
use App\Jobs\RegenerateSeminarCertificatesJob;
use App\Models\Registration;
use App\Models\Seminar;
use Illuminate\Support\Facades\Bus;

describe('RegenerateSeminarCertificatesJob', function () {
    it('fans out one RegenerateCertificateJob per issued certificate of the seminar', function () {
        Bus::fake([RegenerateCertificateJob::class]);

        $seminar = Seminar::factory()->create();
        $issued1 = Registration::factory()->create([
            'seminar_id' => $seminar->id, 'present' => true, 'certificate_code' => 'code-1',
        ]);
        $issued2 = Registration::factory()->create([
            'seminar_id' => $seminar->id, 'present' => true, 'certificate_code' => 'code-2',
        ]);
        Registration::factory()->create([
            'seminar_id' => $seminar->id, 'present' => false, 'certificate_code' => 'code-3',
        ]);
        Registration::factory()->create([
            'seminar_id' => $seminar->id, 'present' => true, 'certificate_code' => null,
        ]);
        Registration::factory()->create(['present' => true, 'certificate_code' => 'other-seminar']);

        (new RegenerateSeminarCertificatesJob($seminar))->handle();

        Bus::assertDispatched(RegenerateCertificateJob::class, 2);
        Bus::assertDispatched(RegenerateCertificateJob::class, fn ($job) => $job->registration->id === $issued1->id);
        Bus::assertDispatched(RegenerateCertificateJob::class, fn ($job) => $job->registration->id === $issued2->id);
    });

    it('has correct tries and backoff values', function () {
        $job = new RegenerateSeminarCertificatesJob(Seminar::factory()->create());

        expect($job->tries)->toBe(3);
        expect($job->backoff)->toBe(60);
    });
});
