<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the user badges table', function () {
    expect(Schema::hasTable('user_badges'))->toBeTrue()
        ->and(Schema::hasColumns('user_badges', [
            'id',
            'user_id',
            'badge_key',
            'earned_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates the user experience events table', function () {
    expect(Schema::hasTable('user_experience_events'))->toBeTrue()
        ->and(Schema::hasColumns('user_experience_events', [
            'id',
            'user_id',
            'reason',
            'source_key',
            'points',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('allows a badge to be earned only once per user', function () {
    $user = User::factory()->create();
    $badge = [
        'user_id' => $user->id,
        'badge_key' => 'first_presence',
        'earned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('user_badges')->insert($badge);

    expect(fn () => DB::table('user_badges')->insert($badge))
        ->toThrow(QueryException::class);
});

it('allows an experience source to award a reason only once per user', function () {
    $user = User::factory()->create();
    $event = [
        'user_id' => $user->id,
        'reason' => 'attendance',
        'source_key' => 'seminar:1',
        'points' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('user_experience_events')->insert($event);

    expect(fn () => DB::table('user_experience_events')->insert($event))
        ->toThrow(QueryException::class);
});

it('deletes badge and experience records when the user is force deleted', function () {
    $user = User::factory()->create();

    DB::table('user_badges')->insert([
        'user_id' => $user->id,
        'badge_key' => 'first_presence',
        'earned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('user_experience_events')->insert([
        'user_id' => $user->id,
        'reason' => 'attendance',
        'source_key' => 'seminar:1',
        'points' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user->forceDelete();

    $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('user_experience_events', ['user_id' => $user->id]);
});
