<?php

use App\Models\Seminar;
use App\Models\User;
use App\Policies\SeminarPolicy;
use Spatie\Permission\Models\Role;

describe('SeminarPolicy', function () {
    beforeEach(function () {
        $this->policy = new SeminarPolicy;
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    });

    describe('viewAny', function () {
        it('allows admin to view any seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows teacher to view any seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies regular user from viewing any seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view any seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->view($user, $seminar))->toBeTrue();
        });

        it('allows teacher to view own seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $user->id]);

            expect($this->policy->view($user, $seminar))->toBeTrue();
        });

        it('denies teacher from viewing others seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $otherUser = User::factory()->create();
            $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->view($user, $seminar))->toBeFalse();
        });

        it('denies regular user from viewing seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $seminar = Seminar::factory()->create();

            expect($this->policy->view($user, $seminar))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows teacher to create seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies regular user from creating seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update any seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->update($user, $seminar))->toBeTrue();
        });

        it('allows teacher to update own seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $user->id]);

            expect($this->policy->update($user, $seminar))->toBeTrue();
        });

        it('denies teacher from updating others seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $otherUser = User::factory()->create();
            $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->update($user, $seminar))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete any seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->delete($user, $seminar))->toBeTrue();
        });

        it('allows teacher to delete own seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $user->id]);

            expect($this->policy->delete($user, $seminar))->toBeTrue();
        });

        it('denies teacher from deleting others seminar', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $otherUser = User::factory()->create();
            $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->delete($user, $seminar))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows admin to restore seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->restore($user, $seminar))->toBeTrue();
        });

        it('denies teacher from restoring seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $user->id]);

            expect($this->policy->restore($user, $seminar))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to force delete seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            $seminar = Seminar::factory()->create();

            expect($this->policy->forceDelete($user, $seminar))->toBeTrue();
        });

        it('denies teacher from force deleting seminars', function () {
            $user = User::factory()->create();
            $user->assignRole('teacher');
            $seminar = Seminar::factory()->create(['created_by' => $user->id]);

            expect($this->policy->forceDelete($user, $seminar))->toBeFalse();
        });
    });
});
