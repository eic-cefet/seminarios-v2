<?php

use App\Models\User;

it('flags incomplete profile when name is one word', function () {
    $user = User::factory()->make(['name' => 'Maria']);
    expect($user->hasIncompleteProfile())->toBeTrue();
});

it('does not flag complete profile when name is two words', function () {
    $user = User::factory()->make(['name' => 'Maria Silva']);
    expect($user->hasIncompleteProfile())->toBeFalse();
});

it('flags the literal OAuth fallback "User"', function () {
    $user = User::factory()->make(['name' => 'User']);
    expect($user->hasIncompleteProfile())->toBeTrue();
});
