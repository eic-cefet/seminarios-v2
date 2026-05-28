<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Ensure roles exist for permission tests
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');

        // CI runs the browser suite with --coverage, whose Xdebug/PCOV
        // instrumentation slows every backend request several-fold. The
        // default 5s Playwright wait is too tight for multi-request flows
        // (register → invalidate → refetch) under that load, which flaked on
        // CI while passing locally. A larger ceiling only extends how long an
        // assertion may wait — passing assertions still resolve immediately.
        Playwright::setTimeout(20_000);
    })
    ->in('Browser');

pest()->extend(TestCase::class)->in('Unit/Config');

pest()->extend(TestCase::class)->in('Unit/Support');

pest()->extend(TestCase::class)->in('Unit/Http');

pest()->extend(TestCase::class)->in('Unit/Providers');

pest()->extend(TestCase::class)->in('Unit/Rules');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
    })
    ->in('Unit/Models');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
    })
    ->in('Unit/Services');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
    })
    ->in('Unit/Policies');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
    })
    ->in('Unit/Factories');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user and authenticate as them.
 */
function actingAsUser(?User $user = null): User
{
    $user ??= User::factory()->create();
    test()->actingAs($user, 'sanctum');

    return $user;
}

/**
 * Create an admin user and authenticate as them.
 */
function actingAsAdmin(?User $user = null): User
{
    $user ??= User::factory()->create();
    $user->assignRole('admin');
    test()->actingAs($user, 'sanctum');

    return $user;
}

/**
 * Create a teacher user and authenticate as them.
 */
function actingAsTeacher(?User $user = null): User
{
    $user ??= User::factory()->create();
    $user->assignRole('teacher');
    test()->actingAs($user, 'sanctum');

    return $user;
}
