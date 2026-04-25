<?php

use App\Models\Seminar;
use App\Models\User;
use App\Services\SeminarVisibilityService;

it('returns the unscoped query for admins', function () {
    $admin = User::factory()->admin()->create();
    Seminar::factory()->count(3)->create();

    expect((new SeminarVisibilityService)
        ->visibleSeminars(Seminar::query(), $admin)
        ->count())->toBe(3);
});

it('restricts teachers to seminars they created', function () {
    $teacher = User::factory()->teacher()->create();
    $other = User::factory()->teacher()->create();
    Seminar::factory()->count(2)->create(['created_by' => $teacher->id]);
    Seminar::factory()->count(3)->create(['created_by' => $other->id]);

    expect((new SeminarVisibilityService)
        ->visibleSeminars(Seminar::query(), $teacher)
        ->count())->toBe(2);
});
