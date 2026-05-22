<?php

use App\Models\Course;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use App\Models\UserStudentData;
use App\Services\SemestralReportService;

it('returns a row per attendee with total minutes for the given semester', function () {
    $course = Course::factory()->create(['name' => 'Sistemas de Informação']);
    $user = User::factory()->create(['name' => 'Test Student']);
    UserStudentData::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'course_situation' => 'studying',
    ]);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'duration_minutes' => 90,
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
    ]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1");

    expect($rows)->toHaveCount(1);

    $row = $rows->first();
    expect($row)->toMatchArray([
        'name' => $user->name,
        'email' => $user->email,
        'course' => 'Sistemas de Informação',
        'total_minutes' => 90,
        'total_hours' => 1.5,
    ]);
    expect($row['presentations'])->toHaveCount(1);
    expect($row['presentations']->first()['duration_minutes'])->toBe(90);
});

it('defaults course to N/A when student data is missing', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'duration_minutes' => 60,
    ]);
    Registration::factory()->create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
    ]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1");

    expect($rows->first()['course'])->toBe('N/A');
});

it('applies type filter via filters array', function () {
    $type1 = SeminarType::factory()->create();
    $type2 = SeminarType::factory()->create();

    $user = User::factory()->create();
    $seminar1 = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'seminar_type_id' => $type1->id,
    ]);
    $seminar2 = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
        'seminar_type_id' => $type2->id,
    ]);

    Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar1->id]);
    Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar2->id]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1", [
        'types' => [$type1->id],
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['presentations'])->toHaveCount(1);
});

it('applies course filter via filters array', function () {
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();

    $student1 = User::factory()->create();
    UserStudentData::create(['user_id' => $student1->id, 'course_id' => $course1->id, 'course_situation' => 'studying']);
    $student2 = User::factory()->create();
    UserStudentData::create(['user_id' => $student2->id, 'course_id' => $course2->id, 'course_situation' => 'studying']);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
    ]);
    Registration::factory()->create(['user_id' => $student1->id, 'seminar_id' => $seminar->id]);
    Registration::factory()->create(['user_id' => $student2->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1", [
        'courses' => [$course1->id],
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['email'])->toBe($student1->email);
});

it('applies situation filter via filters array', function () {
    $course = Course::factory()->create();

    $studying = User::factory()->create();
    UserStudentData::create(['user_id' => $studying->id, 'course_id' => $course->id, 'course_situation' => 'studying']);
    $graduated = User::factory()->create();
    UserStudentData::create(['user_id' => $graduated->id, 'course_id' => $course->id, 'course_situation' => 'graduated']);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
    ]);
    Registration::factory()->create(['user_id' => $studying->id, 'seminar_id' => $seminar->id]);
    Registration::factory()->create(['user_id' => $graduated->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1", [
        'situations' => ['studying'],
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['email'])->toBe($studying->email);
});

it('honors second semester boundaries', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonths(7),
    ]);
    Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.2");

    expect($rows)->toHaveCount(1);
});

it('sorts rows by attendee name', function () {
    $userZ = User::factory()->create(['name' => 'Zé da Silva']);
    $userA = User::factory()->create(['name' => 'Ana Santos']);

    $seminar = Seminar::factory()->create([
        'active' => true,
        'scheduled_at' => now()->startOfYear()->addMonth(),
    ]);
    Registration::factory()->create(['user_id' => $userZ->id, 'seminar_id' => $seminar->id]);
    Registration::factory()->create(['user_id' => $userA->id, 'seminar_id' => $seminar->id]);

    $currentYear = now()->year;
    $rows = app(SemestralReportService::class)->collect("{$currentYear}.1");

    expect($rows->values()->first()['name'])->toBe('Ana Santos');
});
