<?php

use App\Models\SeminarType;
use App\Models\User;
use App\Policies\SeminarTypePolicy;

it('allows admins to manage seminar types', function () {
    $admin = User::factory()->admin()->create();
    $policy = new SeminarTypePolicy;
    $type = SeminarType::factory()->create();

    expect($policy->viewAny($admin))->toBeTrue();
    expect($policy->view($admin, $type))->toBeTrue();
    expect($policy->create($admin))->toBeTrue();
    expect($policy->update($admin, $type))->toBeTrue();
    expect($policy->delete($admin, $type))->toBeTrue();
});

it('denies non-admins from managing seminar types', function () {
    $user = User::factory()->create();
    $policy = new SeminarTypePolicy;
    $type = SeminarType::factory()->create();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $type))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $type))->toBeFalse();
    expect($policy->delete($user, $type))->toBeFalse();
});
