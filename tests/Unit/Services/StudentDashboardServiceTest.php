<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use App\Services\StudentDashboardService;
use App\Support\SemesterRange;

beforeEach(function () {
    $this->service = app(StudentDashboardService::class);
});

describe('listStudents', function () {
    it('lists students with a registration in the given semester', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->student()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00']);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

        $outOfRangeStudent = User::factory()->student()->create();
        $outOfRangeSeminar = Seminar::factory()->create(['scheduled_at' => '2025-03-10 10:00:00']);
        Registration::factory()->create(['user_id' => $outOfRangeStudent->id, 'seminar_id' => $outOfRangeSeminar->id]);

        $result = $this->service->listStudents(SemesterRange::fromString('2026.1'), $admin, null);

        expect($result->total())->toBe(1)
            ->and($result->items()[0]->id)->toBe($student->id);
    });

    it('excludes users with an assigned role (admin/teacher are not students)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00']);
        Registration::factory()->create(['user_id' => $teacher->id, 'seminar_id' => $seminar->id]);

        $result = $this->service->listStudents(SemesterRange::fromString('2026.1'), $admin, null);

        expect($result->total())->toBe(0);
    });

    it('scopes the list to a teacher\'s own seminars', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $ownStudent = User::factory()->student()->create();
        $ownSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00', 'created_by' => $teacher->id]);
        Registration::factory()->create(['user_id' => $ownStudent->id, 'seminar_id' => $ownSeminar->id]);

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');
        $otherStudent = User::factory()->student()->create();
        $otherSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-11 10:00:00', 'created_by' => $otherTeacher->id]);
        Registration::factory()->create(['user_id' => $otherStudent->id, 'seminar_id' => $otherSeminar->id]);

        $result = $this->service->listStudents(SemesterRange::fromString('2026.1'), $teacher, null);

        expect($result->total())->toBe(1)
            ->and($result->items()[0]->id)->toBe($ownStudent->id);
    });

    it('filters by search term across name and email', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->student()->create(['name' => 'Maria Estudante']);
        $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00']);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

        $other = User::factory()->student()->create(['name' => 'João Outro']);
        Registration::factory()->create(['user_id' => $other->id, 'seminar_id' => $seminar->id]);

        $result = $this->service->listStudents(SemesterRange::fromString('2026.1'), $admin, 'Maria');

        expect($result->total())->toBe(1)
            ->and($result->items()[0]->id)->toBe($student->id);
    });
});

describe('forStudent', function () {
    it('computes attended, missed and upcoming totals', function () {
        Carbon\Carbon::setTestNow('2026-03-20 00:00:00');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->student()->create();

        $attendedSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-01 10:00:00', 'duration_minutes' => 60]);
        Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $attendedSeminar->id]);

        $missedSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-05 10:00:00', 'duration_minutes' => 60]);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $missedSeminar->id, 'present' => false]);

        $upcomingSeminar = Seminar::factory()->create(['scheduled_at' => '2026-06-01 10:00:00', 'duration_minutes' => 60]);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $upcomingSeminar->id, 'present' => false]);

        $result = $this->service->forStudent($student, SemesterRange::fromString('2026.1'), $admin);

        expect($result['totals'])->toBe([
            'attended' => 1,
            'missed' => 1,
            'upcoming' => 1,
            'hours_attended' => 1.0,
        ]);

        Carbon\Carbon::setTestNow();
    });

    it('groups hours and counts by presentation type, counting only attended hours', function () {
        Carbon\Carbon::setTestNow('2026-03-20 00:00:00');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->student()->create();
        $type = SeminarType::factory()->create(['name' => 'Palestra']);

        $attended = Seminar::factory()->create([
            'scheduled_at' => '2026-03-01 10:00:00',
            'duration_minutes' => 120,
            'seminar_type_id' => $type->id,
        ]);
        Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $attended->id]);

        $missed = Seminar::factory()->create([
            'scheduled_at' => '2026-03-02 10:00:00',
            'duration_minutes' => 60,
            'seminar_type_id' => $type->id,
        ]);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $missed->id, 'present' => false]);

        $result = $this->service->forStudent($student, SemesterRange::fromString('2026.1'), $admin);

        expect($result['by_type'])->toBe([
            ['type' => 'Palestra', 'attended' => 1, 'missed' => 1, 'hours' => 2.0],
        ]);

        Carbon\Carbon::setTestNow();
    });

    it('scopes a teacher\'s view of a student to only their own seminars', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');
        $student = User::factory()->student()->create();

        $ownSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-01 10:00:00', 'created_by' => $teacher->id]);
        Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $ownSeminar->id]);

        $otherSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-02 10:00:00', 'created_by' => $otherTeacher->id]);
        Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $otherSeminar->id]);

        $result = $this->service->forStudent($student, SemesterRange::fromString('2026.1'), $teacher);

        expect($result['totals']['attended'])->toBe(1)
            ->and($result['registrations'])->toHaveCount(1);
    });
});
