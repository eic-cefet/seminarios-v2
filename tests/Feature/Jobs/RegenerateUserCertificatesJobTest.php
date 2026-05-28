<?php

use App\Jobs\RegenerateCertificateJob;
use App\Jobs\RegenerateUserCertificatesJob;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Bus;

describe('RegenerateUserCertificatesJob', function () {
    it('dispatches one RegenerateCertificateJob per qualifying certificate', function () {
        Bus::fake();

        $user = User::factory()->create();
        $reg1 = Registration::factory()->create(['user_id' => $user->id, 'present' => true, 'certificate_code' => 'A']);
        $reg2 = Registration::factory()->create(['user_id' => $user->id, 'present' => true, 'certificate_code' => 'B']);

        (new RegenerateUserCertificatesJob($user))->handle();

        Bus::assertDispatched(RegenerateCertificateJob::class, 2);
        Bus::assertDispatched(RegenerateCertificateJob::class, fn ($job) => $job->registration->is($reg1));
        Bus::assertDispatched(RegenerateCertificateJob::class, fn ($job) => $job->registration->is($reg2));
    });

    it('skips registrations without a certificate_code', function () {
        Bus::fake();

        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id, 'present' => true, 'certificate_code' => null]);

        (new RegenerateUserCertificatesJob($user))->handle();

        Bus::assertNotDispatched(RegenerateCertificateJob::class);
    });

    it('skips registrations where the user was not present', function () {
        Bus::fake();

        $user = User::factory()->create();
        Registration::factory()->create(['user_id' => $user->id, 'present' => false, 'certificate_code' => 'C']);

        (new RegenerateUserCertificatesJob($user))->handle();

        Bus::assertNotDispatched(RegenerateCertificateJob::class);
    });

    it('is a no-op when the user has no registrations', function () {
        Bus::fake();

        $user = User::factory()->create();

        (new RegenerateUserCertificatesJob($user))->handle();

        Bus::assertNotDispatched(RegenerateCertificateJob::class);
    });

    it('is not ShouldBeUnique so the dispatcher fully controls fan-out frequency', function () {
        // Debouncing lives in UserCertificateRegenerationDispatcher (cache+lock).
        // Layering an additional uniqueness lock here would silently drop the
        // delayed fan-out the dispatcher scheduled and leave certificates stale.
        $job = new RegenerateUserCertificatesJob(User::factory()->create());

        expect($job)->not->toBeInstanceOf(ShouldBeUnique::class);
    });

    it('ignores certificates that belong to other users', function () {
        Bus::fake();

        $user = User::factory()->create();
        $other = User::factory()->create();
        Registration::factory()->create(['user_id' => $other->id, 'present' => true, 'certificate_code' => 'D']);

        (new RegenerateUserCertificatesJob($user))->handle();

        Bus::assertNotDispatched(RegenerateCertificateJob::class);
    });
});
