<?php

use App\Models\User;
use App\Rules\FullName;

it('always generates a name that satisfies the FullName rule', function () {
    foreach (range(1, 50) as $ignored) {
        $name = User::factory()->make()->name;

        expect(FullName::passes($name))->toBeTrue(
            "Factory produced a name that fails FullName: [{$name}]"
        );
    }
});
