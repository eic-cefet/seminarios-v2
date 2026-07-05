<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

describe('User calendar_feed_token', function () {
    it('hides calendar_feed_token from serialization', function () {
        $user = User::factory()->create(['calendar_feed_token' => str_repeat('a', 48)]);

        expect($user->toArray())->not->toHaveKey('calendar_feed_token');
    });

    it('does not write an audit row when only calendar_feed_token changes', function () {
        $user = User::factory()->create();
        $before = AuditLog::query()->count();

        $user->forceFill(['calendar_feed_token' => Str::random(48)])->save();

        expect(AuditLog::query()->count())->toBe($before);
    });

    it('does not include calendar_feed_token in the creation audit values', function () {
        $user = User::factory()->create(['calendar_feed_token' => str_repeat('b', 48)]);

        $log = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        expect(json_encode($log?->new_values ?? []))->not->toContain(str_repeat('b', 48));
    });

    it('enforces uniqueness on calendar_feed_token', function () {
        User::factory()->create(['calendar_feed_token' => str_repeat('c', 48)]);

        expect(fn () => User::factory()->create(['calendar_feed_token' => str_repeat('c', 48)]))
            ->toThrow(UniqueConstraintViolationException::class);
    });

    it('allows many users with a null calendar_feed_token', function () {
        User::factory()->count(2)->create();

        expect(User::query()->whereNull('calendar_feed_token')->count())->toBeGreaterThanOrEqual(2);
    });
});
