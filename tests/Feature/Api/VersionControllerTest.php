<?php

use App\Http\Controllers\Api\VersionController;
use Illuminate\Support\Facades\Cache;

it('returns the cached version', function () {
    Cache::forever(VersionController::CACHE_KEY, 'v1.2.3');

    $response = $this->getJson('/api/version');

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'version' => 'v1.2.3',
            ],
        ]);
});

it('returns unknown when no version is cached', function () {
    Cache::forget(VersionController::CACHE_KEY);

    $response = $this->getJson('/api/version');

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'version' => 'unknown',
            ],
        ]);
});
