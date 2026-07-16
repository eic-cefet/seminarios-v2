<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Policies\UserPolicy;

describe('viewGamification', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy;
    });

    it('allows an administrator to view regular student gamification', function () {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        expect(method_exists($this->policy, 'viewGamification'))->toBeTrue();
        expect($this->policy->viewGamification($admin, $student))->toBeTrue();
    });

    it('allows a teacher only when the regular student registered for their seminar', function () {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $ownSeminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        Registration::factory()->for($student)->for($ownSeminar)->create();

        expect(method_exists($this->policy, 'viewGamification'))->toBeTrue();
        expect($this->policy->viewGamification($teacher, $student))->toBeTrue();
    });

    it('denies a teacher when the student has no registration for their seminar', function () {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $otherSeminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);
        Registration::factory()->for($student)->for($otherSeminar)->create();

        expect(method_exists($this->policy, 'viewGamification'))->toBeTrue();
        expect($this->policy->viewGamification($teacher, $student))->toBeFalse();
    });

    it('denies regular users', function () {
        $user = User::factory()->create();
        $student = User::factory()->student()->create();

        expect(method_exists($this->policy, 'viewGamification'))->toBeTrue();
        expect($this->policy->viewGamification($user, $student))->toBeFalse();
    });

    it('denies staff targets for every staff viewer', function (string $viewerRole, string $targetRole) {
        $viewer = User::factory()->create();
        $viewer->assignRole($viewerRole);
        $target = User::factory()->create();
        $target->assignRole($targetRole);

        expect(method_exists($this->policy, 'viewGamification'))->toBeTrue();
        expect($this->policy->viewGamification($viewer, $target))->toBeFalse();
    })->with([
        ['admin', 'admin'],
        ['admin', 'teacher'],
        ['teacher', 'admin'],
        ['teacher', 'teacher'],
    ]);
});

it('preserves the existing user policy abilities', function () {
    $policy = new UserPolicy;
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create();
    $user = User::factory()->create();
    $target = User::factory()->create();

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->viewAny($teacher))->toBeTrue()
        ->and($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($admin, $target))->toBeTrue()
        ->and($policy->view($teacher, $target))->toBeFalse()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->create($teacher))->toBeFalse()
        ->and($policy->update($admin, $target))->toBeTrue()
        ->and($policy->update($teacher, $target))->toBeFalse()
        ->and($policy->delete($admin, $target))->toBeTrue()
        ->and($policy->delete($admin, $admin))->toBeFalse()
        ->and($policy->restore($admin, $target))->toBeTrue()
        ->and($policy->forceDelete($admin, $target))->toBeTrue()
        ->and($policy->forceDelete($admin, $admin))->toBeFalse()
        ->and($policy->viewLgpdData($admin))->toBeTrue()
        ->and($policy->exportLgpdData($admin))->toBeTrue()
        ->and($policy->anonymizeUser($admin))->toBeTrue();
});
