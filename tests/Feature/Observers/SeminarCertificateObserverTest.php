<?php

use App\Jobs\RegenerateSeminarCertificatesJob;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use Illuminate\Support\Facades\Bus;

function seminarWithIssuedCertificate(): Seminar
{
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
    Registration::factory()->create([
        'seminar_id' => $seminar->id,
        'present' => true,
        'certificate_code' => 'issued-'.$seminar->id,
    ]);

    return $seminar;
}

describe('SeminarCertificateObserver', function () {
    it('dispatches regeneration when the seminar name changes after issuance', function () {
        Bus::fake([RegenerateSeminarCertificatesJob::class]);
        $seminar = seminarWithIssuedCertificate();

        $seminar->update(['name' => 'Título corrigido']);

        Bus::assertDispatched(RegenerateSeminarCertificatesJob::class, fn ($job) => $job->seminar->id === $seminar->id);
    });

    it('dispatches regeneration when the seminar is rescheduled after issuance', function () {
        Bus::fake([RegenerateSeminarCertificatesJob::class]);
        $seminar = seminarWithIssuedCertificate();

        $seminar->update(['scheduled_at' => now()->addDays(30)]);

        Bus::assertDispatched(RegenerateSeminarCertificatesJob::class);
    });

    it('dispatches regeneration when the seminar type changes after issuance', function () {
        Bus::fake([RegenerateSeminarCertificatesJob::class]);
        $seminar = seminarWithIssuedCertificate();
        $newType = SeminarType::factory()->create();

        $seminar->update(['seminar_type_id' => $newType->id]);

        Bus::assertDispatched(RegenerateSeminarCertificatesJob::class);
    });

    it('does not dispatch for certificate-invisible field changes', function () {
        Bus::fake([RegenerateSeminarCertificatesJob::class]);
        $seminar = seminarWithIssuedCertificate();

        $seminar->update(['description' => 'Nova descrição', 'room_link' => 'https://meet.example.com/x']);

        Bus::assertNotDispatched(RegenerateSeminarCertificatesJob::class);
    });

    it('does not dispatch when the seminar has no issued certificates', function () {
        Bus::fake([RegenerateSeminarCertificatesJob::class]);
        $seminar = Seminar::factory()->create();
        Registration::factory()->create([
            'seminar_id' => $seminar->id, 'present' => true, 'certificate_code' => null,
        ]);

        $seminar->update(['name' => 'Outro título']);

        Bus::assertNotDispatched(RegenerateSeminarCertificatesJob::class);
    });
});
