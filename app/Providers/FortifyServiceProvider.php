<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::twoFactorChallengeView(fn () => response()->noContent());
        Fortify::confirmPasswordView(fn () => response()->noContent());
    }
}
