<?php

namespace App\Services;

class FeatureFlags
{
    public static function enabled(string $feature): bool
    {
        return (bool) config("features.{$feature}.enabled", false);
    }

    public static function disabled(string $feature): bool
    {
        return ! static::enabled($feature);
    }

    public static function sampleRate(string $feature): int
    {
        return (int) config("features.{$feature}.sample_rate", 100);
    }

    /**
     * Check if a feature is enabled AND passes the sample rate roll.
     */
    public static function shouldRun(string $feature): bool
    {
        if (static::disabled($feature)) {
            return false;
        }

        $rate = static::sampleRate($feature);

        if ($rate >= 100) {
            return true;
        }

        if ($rate <= 0) {
            return false;
        }

        return random_int(1, 100) <= $rate;
    }
}
