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
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Seminar::class, SeminarPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(SeminarLocation::class, SeminarLocationPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);
    }
}
