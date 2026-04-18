<?php

use Illuminate\Support\Facades\DB;

describe('PruneExpiredCacheCommand', function () {
    beforeEach(function () {
        config(['cache.default' => 'database']);
        DB::table('cache')->truncate();
        DB::table('cache_locks')->truncate();
    });

    it('deletes rows whose expiration has passed', function () {
        $now = now()->timestamp;
        DB::table('cache')->insert([
            ['key' => 'expired-1', 'value' => 'x', 'expiration' => $now - 60],
            ['key' => 'expired-2', 'value' => 'x', 'expiration' => $now - 1],
            ['key' => 'fresh', 'value' => 'x', 'expiration' => $now + 600],
        ]);

        $this->artisan('cache:prune-expired')
            ->expectsOutputToContain('Pruned 2 expired cache row(s).')
            ->assertExitCode(0);

        expect(DB::table('cache')->pluck('key')->all())->toBe(['fresh']);
    });

    it('prunes expired cache_locks alongside cache entries', function () {
        $now = now()->timestamp;
        DB::table('cache')->insert([
            'key' => 'stale', 'value' => 'x', 'expiration' => $now - 5,
        ]);
        DB::table('cache_locks')->insert([
            'key' => 'stale-lock', 'owner' => 'worker-1', 'expiration' => $now - 5,
        ]);

        $this->artisan('cache:prune-expired')
            ->expectsOutputToContain('Pruned 2 expired cache row(s).')
            ->assertExitCode(0);

        expect(DB::table('cache')->count())->toBe(0);
        expect(DB::table('cache_locks')->count())->toBe(0);
    });

    it('does nothing when default cache store is not database', function () {
        config(['cache.default' => 'array']);
        DB::table('cache')->insert([
            'key' => 'expired', 'value' => 'x', 'expiration' => now()->timestamp - 60,
        ]);

        $this->artisan('cache:prune-expired')
            ->expectsOutputToContain('Skipping')
            ->assertExitCode(0);

        expect(DB::table('cache')->count())->toBe(1);
    });

    it('reports zero when nothing is expired', function () {
        DB::table('cache')->insert([
            'key' => 'fresh', 'value' => 'x', 'expiration' => now()->timestamp + 600,
        ]);

        $this->artisan('cache:prune-expired')
            ->expectsOutputToContain('Pruned 0 expired cache row(s).')
            ->assertExitCode(0);

        expect(DB::table('cache')->count())->toBe(1);
    });
});

describe('Scheduled cache prune', function () {
    it('schedules cache:prune-expired', function () {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('cache:prune-expired')
            ->assertExitCode(0);
    });
});
