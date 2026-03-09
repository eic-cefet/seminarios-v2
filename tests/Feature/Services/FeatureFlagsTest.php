<?php

use App\Services\FeatureFlags;

it('returns enabled true when feature is enabled', function () {
    config(['features.test_feature.enabled' => true]);

    expect(FeatureFlags::enabled('test_feature'))->toBeTrue();
});

it('returns enabled false when feature is disabled', function () {
    config(['features.test_feature.enabled' => false]);

    expect(FeatureFlags::enabled('test_feature'))->toBeFalse();
});

it('returns enabled false for unconfigured features', function () {
    expect(FeatureFlags::enabled('nonexistent'))->toBeFalse();
});

it('returns disabled as inverse of enabled', function () {
    config(['features.test_feature.enabled' => true]);
    expect(FeatureFlags::disabled('test_feature'))->toBeFalse();

    config(['features.test_feature.enabled' => false]);
    expect(FeatureFlags::disabled('test_feature'))->toBeTrue();
});

it('returns sample rate from config', function () {
    config(['features.test_feature.sample_rate' => 75]);

    expect(FeatureFlags::sampleRate('test_feature'))->toBe(75);
});

it('defaults sample rate to 100 when not configured', function () {
    config(['features.test_feature' => ['enabled' => true]]);

    expect(FeatureFlags::sampleRate('test_feature'))->toBe(100);
});

it('shouldRun returns false when feature is disabled', function () {
    config(['features.test_feature' => ['enabled' => false, 'sample_rate' => 100]]);

    expect(FeatureFlags::shouldRun('test_feature'))->toBeFalse();
});

it('shouldRun returns true when enabled with 100 percent rate', function () {
    config(['features.test_feature' => ['enabled' => true, 'sample_rate' => 100]]);

    expect(FeatureFlags::shouldRun('test_feature'))->toBeTrue();
});

it('shouldRun returns false when sample rate is zero', function () {
    config(['features.test_feature' => ['enabled' => true, 'sample_rate' => 0]]);

    expect(FeatureFlags::shouldRun('test_feature'))->toBeFalse();
});

it('shouldRun respects intermediate sample rate', function () {
    config(['features.test_feature' => ['enabled' => true, 'sample_rate' => 50]]);

    $results = collect(range(1, 200))->map(fn () => FeatureFlags::shouldRun('test_feature'));

    // With 200 runs at 50%, we should get both true and false
    expect($results->contains(true))->toBeTrue();
    expect($results->contains(false))->toBeTrue();
});

it('shouldRun returns true when enabled and sample rate defaults to 100', function () {
    config(['features.test_feature' => ['enabled' => true]]);

    expect(FeatureFlags::shouldRun('test_feature'))->toBeTrue();
});
