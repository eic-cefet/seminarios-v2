<?php

it('keeps faker available to runtime database seeders', function () {
    $composer = json_decode(
        file_get_contents(base_path('composer.json')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['require'])
        ->toHaveKey('fakerphp/faker')
        ->and($composer['require-dev'])
        ->not->toHaveKey('fakerphp/faker');
});
