<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Spatie\Permission\Models\Role;

describe('UserPolicy', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy;
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    });

    describe('viewAny', function () {
        it('allows admin to view any users', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies regular user from viewing any users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view a user', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $target = User::factory()->create();

            expect($this->policy->view($admin, $target))->toBeTrue();
        });

        it('denies regular user from viewing other users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $target = User::factory()->create();

            expect($this->policy->view($user, $target))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create users', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies regular user from creating users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update a user', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $target = User::factory()->create();

            expect($this->policy->update($admin, $target))->toBeTrue();
        });

        it('denies regular user from updating other users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $target = User::factory()->create();

            expect($this->policy->update($user, $target))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete other users', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $target = User::factory()->create();

            expect($this->policy->delete($admin, $target))->toBeTrue();
        });

        it('denies admin from deleting themselves', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');

            expect($this->policy->delete($admin, $admin))->toBeFalse();
        });

        it('denies regular user from deleting users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $target = User::factory()->create();

            expect($this->policy->delete($user, $target))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows admin to restore users', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $target = User::factory()->create();

            expect($this->policy->restore($admin, $target))->toBeTrue();
        });

        it('denies regular user from restoring users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $target = User::factory()->create();

            expect($this->policy->restore($user, $target))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to force delete other users', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');
            $target = User::factory()->create();

            expect($this->policy->forceDelete($admin, $target))->toBeTrue();
        });

        it('denies admin from force deleting themselves', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');

            expect($this->policy->forceDelete($admin, $admin))->toBeFalse();
        });

        it('denies regular user from force deleting users', function () {
            $user = User::factory()->create();
            $user->assignRole('user');
            $target = User::factory()->create();

            expect($this->policy->forceDelete($user, $target))->toBeFalse();
        });
    });
});
