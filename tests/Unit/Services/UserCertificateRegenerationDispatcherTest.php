<?php

use App\Jobs\RegenerateUserCertificatesJob;
use App\Models\User;
use App\Services\UserCertificateRegenerationDispatcher;
use App\Support\Locking\LockKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

describe('UserCertificateRegenerationDispatcher', function () {
    beforeEach(function () {
        Bus::fake();
        Cache::flush();
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    it('dispatches immediately when no recent dispatch is cached', function () {
        $user = User::factory()->create();

        app(UserCertificateRegenerationDispatcher::class)->dispatchFor($user);

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, 1);
        Bus::assertDispatched(
            RegenerateUserCertificatesJob::class,
            fn ($job) => $job->delay === null && $job->user->is($user),
        );
    });

    it('schedules the second dispatch for cooldown_end when called inside the cooldown window', function () {
        $now = Carbon::parse('2026-05-27 10:00:00');
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $dispatcher = app(UserCertificateRegenerationDispatcher::class);

        $dispatcher->dispatchFor($user); // immediate at t=0

        Carbon::setTestNow($now->copy()->addMinutes(2));
        $dispatcher->dispatchFor($user); // should schedule for t=10

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, 2);

        $delayedHits = 0;
        Bus::assertDispatched(RegenerateUserCertificatesJob::class, function ($job) use ($now, &$delayedHits) {
            if ($job->delay instanceof DateTimeInterface) {
                expect(Carbon::instance($job->delay)->equalTo($now->copy()->addMinutes(10)))->toBeTrue();
                $delayedHits++;
            }

            return true;
        });
        expect($delayedHits)->toBe(1);
    });

    it('does nothing while a future-scheduled fan-out is still pending', function () {
        $now = Carbon::parse('2026-05-27 10:00:00');
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $dispatcher = app(UserCertificateRegenerationDispatcher::class);

        $dispatcher->dispatchFor($user); // immediate (t=0)

        Carbon::setTestNow($now->copy()->addMinutes(2));
        $dispatcher->dispatchFor($user); // scheduled for t=10

        // Subsequent updates BEFORE the scheduled time should noop —
        // the pending fan-out reloads the user from the DB and picks
        // up the latest name when it runs.
        Carbon::setTestNow($now->copy()->addMinutes(5));
        $dispatcher->dispatchFor($user);

        Carbon::setTestNow($now->copy()->addMinutes(7));
        $dispatcher->dispatchFor($user);

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, 2);
    });

    it('dispatches immediately when called after the cooldown has fully expired', function () {
        $now = Carbon::parse('2026-05-27 10:00:00');
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $dispatcher = app(UserCertificateRegenerationDispatcher::class);

        $dispatcher->dispatchFor($user); // immediate at t=0

        Carbon::setTestNow($now->copy()->addMinutes(11)); // past the 10-min cooldown
        $dispatcher->dispatchFor($user);

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, 2);

        $immediate = 0;
        Bus::assertDispatched(RegenerateUserCertificatesJob::class, function ($job) use (&$immediate) {
            if ($job->delay === null) {
                $immediate++;
            }

            return true;
        });
        expect($immediate)->toBe(2);
    });

    it('swallows lock timeouts without propagating them up the save', function () {
        $user = User::factory()->create();

        // Hold the underlying lock for longer than the Mutex's 2s wait window
        // so the dispatcher's Mutex::for(...)->protect(...) throws
        // LockTimeoutException internally — which the dispatcher must catch
        // (otherwise the user's profile-save HTTP request would 500).
        $heldLock = Cache::lock(LockKey::userCertificateRegeneration($user->id), 30);
        expect($heldLock->get())->toBeTrue();
        Log::spy();

        try {
            // Must not throw.
            app(UserCertificateRegenerationDispatcher::class)->dispatchFor($user);
        } finally {
            $heldLock->release();
        }

        Bus::assertNotDispatched(RegenerateUserCertificatesJob::class);
        Log::shouldHaveReceived('warning')->once();
    });

    it('isolates the debounce state per user', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $dispatcher = app(UserCertificateRegenerationDispatcher::class);

        $dispatcher->dispatchFor($userA);
        $dispatcher->dispatchFor($userB);
        // Both immediate — user B is unaffected by user A's cooldown.
        $dispatcher->dispatchFor($userA); // userA inside cooldown → schedule
        $dispatcher->dispatchFor($userB); // userB inside cooldown → schedule

        Bus::assertDispatched(RegenerateUserCertificatesJob::class, 4);
        Bus::assertDispatched(
            RegenerateUserCertificatesJob::class,
            fn ($job) => $job->user->is($userA),
        );
        Bus::assertDispatched(
            RegenerateUserCertificatesJob::class,
            fn ($job) => $job->user->is($userB),
        );
    });
});
