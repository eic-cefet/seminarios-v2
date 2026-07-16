<?php

use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;

describe('GET /api/admin/students/{student}/gamification', function () {
    it('allows an admin to view a regular students aggregate profile', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();
        $student->badges()->create([
            'badge_key' => BadgeKey::FirstPresence,
            'earned_at' => now(),
        ]);
        $student->experienceEvents()->create([
            'reason' => ExperienceReason::Attendance,
            'source_key' => 'attendance:admin-view',
            'points' => 100,
        ]);

        $this->getJson("/api/admin/students/{$student->id}/gamification")
            ->assertOk()
            ->assertJsonPath('data.progress.total_xp', 100)
            ->assertJsonPath('data.summary.earned_badges', 1)
            ->assertJsonPath('data.recent_badges.0.key', 'first_presence')
            ->assertJsonPath('data.recent_badges.0.description', 'Participe de uma apresentação.')
            ->assertJsonMissingPath('data.recent_badges.0.metric')
            ->assertJsonMissingPath('data.recent_badges.0.threshold')
            ->assertJsonMissingPath('data.experience_events')
            ->assertJsonMissingPath('data.events');
    });

    it('allows a teacher to view a regular student registered for their seminar', function () {
        $teacher = actingAsTeacher();
        $student = User::factory()->student()->create();
        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        Registration::factory()->for($student)->for($seminar)->create();
        $student->experienceEvents()->create([
            'reason' => ExperienceReason::Attendance,
            'source_key' => 'attendance:teacher-view',
            'points' => 300,
        ]);

        $this->getJson("/api/admin/students/{$student->id}/gamification")
            ->assertOk()
            ->assertJsonPath('data.progress.total_xp', 300)
            ->assertJsonMissingPath('data.experience_events')
            ->assertJsonMissingPath('data.events');
    });

    it('forbids a teacher from viewing a student without a registration for their seminar', function () {
        actingAsTeacher();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/gamification")
            ->assertForbidden();
    });

    it('forbids regular users from the admin route group', function () {
        actingAsUser();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/gamification")
            ->assertForbidden();
    });

    it('returns not found when staff target an administrator or teacher', function (string $targetRole) {
        actingAsAdmin();
        $staff = User::factory()->create();
        $staff->assignRole($targetRole);

        $this->getJson("/api/admin/students/{$staff->id}/gamification")
            ->assertNotFound();
    })->with(['admin', 'teacher']);

    it('returns not found for an unknown student', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/students/999999/gamification')
            ->assertNotFound();
    });

    it('returns not found for a deleted student', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();
        $student->delete();

        $this->getJson("/api/admin/students/{$student->id}/gamification")
            ->assertNotFound();
    });
});
