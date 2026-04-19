<?php

use App\Http\Controllers\Api\VersionController;
use Illuminate\Support\Facades\Cache;

it('stores the given version in cache', function () {
    Cache::forget(VersionController::CACHE_KEY);

    $this->artisan('version:set', ['version' => 'v2.0.0'])
        ->expectsOutputToContain('Version set to v2.0.0.')
        ->assertExitCode(0);

    expect(Cache::get(VersionController::CACHE_KEY))->toBe('v2.0.0');
});

it('fails when the version is empty', function () {
    $this->artisan('version:set', ['version' => '   '])
        ->expectsOutputToContain('Version cannot be empty.')
        ->assertExitCode(1);
});
