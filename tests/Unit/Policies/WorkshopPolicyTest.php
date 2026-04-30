<?php

use App\Models\User;
use App\Models\Workshop;
use App\Policies\WorkshopPolicy;

it('allows admins to manage workshops', function () {
    $admin = User::factory()->admin()->create();
    $policy = new WorkshopPolicy;
    $workshop = Workshop::factory()->create();

    expect($policy->viewAny($admin))->toBeTrue();
    expect($policy->view($admin, $workshop))->toBeTrue();
    expect($policy->create($admin))->toBeTrue();
    expect($policy->update($admin, $workshop))->toBeTrue();
    expect($policy->delete($admin, $workshop))->toBeTrue();
});

it('denies non-admins from managing workshops', function () {
    $user = User::factory()->create();
    $policy = new WorkshopPolicy;
    $workshop = Workshop::factory()->create();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $workshop))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $workshop))->toBeFalse();
    expect($policy->delete($user, $workshop))->toBeFalse();
});
