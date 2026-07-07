<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Policies\RegistrationPolicy;
use Spatie\Permission\Models\Role;

describe('RegistrationPolicy', function () {
    beforeEach(function () {
        $this->policy = new RegistrationPolicy;
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
    });

    describe('viewAny', function () {
        it('allows admin to view any registrations', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies regular user from viewing any registrations', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('denies user without role from viewing any registrations', function () {
            $user = User::factory()->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view a registration', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $registration = Registration::factory()->create();

            expect($this->policy->view($user, $registration))->toBeTrue();
        });

        it('denies regular user from viewing a registration', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $registration = Registration::factory()->create();

            expect($this->policy->view($user, $registration))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create registrations on any seminar', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->create($admin, $seminar))->toBeTrue();
        });

        it('allows teacher to create registrations on their own seminar', function () {
            $teacher = User::factory()->create();
            $teacher->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);

            expect($this->policy->create($teacher, $seminar))->toBeTrue();
        });

        it('denies teacher from creating registrations on another teacher\'s seminar', function () {
            $teacher = User::factory()->create();
            $teacher->assignRole('teacher');
            $otherTeacher = User::factory()->create();
            $seminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);

            expect($this->policy->create($teacher, $seminar))->toBeFalse();
        });

        it('denies regular user from creating registrations', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $seminar = Seminar::factory()->create();

            expect($this->policy->create($user, $seminar))->toBeFalse();
        });
    });

    describe('updatePresence', function () {
        it('allows admin to update presence', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $registration = Registration::factory()->create();

            expect($this->policy->updatePresence($user, $registration))->toBeTrue();
        });

        it('denies regular user from updating presence', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $registration = Registration::factory()->create();

            expect($this->policy->updatePresence($user, $registration))->toBeFalse();
        });
    });
});
