<?php

namespace App\Providers;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\Subject;
use App\Models\User;
use App\Policies\RegistrationPolicy;
use App\Policies\SeminarLocationPolicy;
use App\Policies\SeminarPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $maxAttempts = (int) env('LOGIN_RATE_LIMIT', 5);

            return Limit::perMinute($maxAttempts)->by(
                $request->input('email').'|'.$request->ip()
            );
        });

        Gate::policy(Seminar::class, SeminarPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(SeminarLocation::class, SeminarLocationPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);
    }
}
