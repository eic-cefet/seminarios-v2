<?php

use App\Jobs\RegenerateUserCertificatesJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

describe('User name-change side effects', function () {
    it('dispatches RegenerateUserCertificatesJob when the name column changes', function () {
        Bus::fake();

        $user = User::factory()->create(['name' => 'Old Name']);
        $user->update(['name' => 'New Name']);

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, fn ($job) => $job->user->is($user));
    });

    it('does not dispatch RegenerateUserCertificatesJob when only other columns change', function () {
        Bus::fake();

        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.test']);
        $user->update(['email' => 'new@example.test']);

        Bus::assertNotDispatched(RegenerateUserCertificatesJob::class);
    });

    it('does not dispatch on initial creation', function () {
        Bus::fake();

        User::factory()->create(['name' => 'Brand New']);

        Bus::assertNotDispatched(RegenerateUserCertificatesJob::class);
    });

    it('does not dispatch when the name is saved unchanged', function () {
        Bus::fake();

        $user = User::factory()->create(['name' => 'Same Name']);
        $user->update(['name' => 'Same Name']);

        Bus::assertNotDispatched(RegenerateUserCertificatesJob::class);
    });
});
