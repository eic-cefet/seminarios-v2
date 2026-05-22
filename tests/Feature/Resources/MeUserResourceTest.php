<?php

use App\Http\Resources\MeUserResource;
use App\Models\Course;
use App\Models\User;
use App\Models\UserStudentData;
use Spatie\Permission\Models\Role;

it('serializes the canonical /me payload with all expected keys', function () {
    Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

    $user = User::factory()->student()->create();
    $user->assignRole('User');

    $payload = (new MeUserResource($user))->resolve();

    expect($payload)->toHaveKeys([
        'id',
        'name',
        'email',
        'email_verified_at',
        'anonymization_requested_at',
        'roles',
        'two_factor_enabled',
        'needs_profile_completion',
        'student_data',
    ]);
});

it('returns the same shape the FormatsUserResponse trait used to return', function () {
    Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

    $user = User::factory()->create(['name' => 'Maria Silva']);
    $user->assignRole('User');

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['id'])->toBe($user->id)
        ->and($payload['name'])->toBe($user->name)
        ->and($payload['email'])->toBe($user->email)
        ->and($payload['roles'])->toBe(['User'])
        ->and($payload['two_factor_enabled'])->toBeFalse()
        ->and($payload['needs_profile_completion'])->toBeFalse()
        ->and($payload['anonymization_requested_at'])->toBeNull()
        ->and($payload['student_data'])->toBeNull();
});

it('flags needs_profile_completion true for a single-word name', function () {
    $user = User::factory()->create(['name' => 'Maria']);

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['needs_profile_completion'])->toBeTrue();
});

it('includes nested course data inside student_data', function () {
    $course = Course::factory()->create(['name' => 'Engenharia de Computação']);
    $user = User::factory()->create();

    UserStudentData::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['student_data'])->toBeArray()
        ->and($payload['student_data']['course'])->toBe([
            'id' => $course->id,
            'name' => 'Engenharia de Computação',
        ]);
});

it('returns null course when student_data has no course', function () {
    $user = User::factory()->create();

    UserStudentData::factory()->create([
        'user_id' => $user->id,
        'course_id' => null,
    ]);

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['student_data'])->toBeArray()
        ->and($payload['student_data']['course'])->toBeNull();
});

it('flags two_factor_enabled true once the user confirms 2FA', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['two_factor_enabled'])->toBeTrue();
});

it('exposes anonymization_requested_at as ISO-8601', function () {
    $when = now()->subDay();
    $user = User::factory()->create([
        'anonymization_requested_at' => $when,
    ]);

    $payload = (new MeUserResource($user))->resolve();

    expect($payload['anonymization_requested_at'])->toBe($when->toIso8601String());
});
