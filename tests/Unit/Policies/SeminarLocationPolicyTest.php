<?php

use App\Models\SeminarLocation;
use App\Models\User;
use App\Policies\SeminarLocationPolicy;

it('allows admins to manage seminar locations', function () {
    $admin = User::factory()->admin()->create();
    $policy = new SeminarLocationPolicy;
    $location = SeminarLocation::factory()->create();

    expect($policy->viewAny($admin))->toBeTrue();
    expect($policy->view($admin, $location))->toBeTrue();
    expect($policy->create($admin))->toBeTrue();
    expect($policy->update($admin, $location))->toBeTrue();
    expect($policy->delete($admin, $location))->toBeTrue();
});

it('denies non-admins from managing seminar locations', function () {
    $user = User::factory()->create();
    $policy = new SeminarLocationPolicy;
    $location = SeminarLocation::factory()->create();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $location))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $location))->toBeFalse();
    expect($policy->delete($user, $location))->toBeFalse();
});
