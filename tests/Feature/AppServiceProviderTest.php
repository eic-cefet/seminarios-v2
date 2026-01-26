<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

it('forces https scheme when force_https config is true', function () {
    config(['app.force_https' => true]);

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    expect(URL::formatScheme())->toBe('https://');
});

it('does not force https scheme when force_https config is false', function () {
    config(['app.force_https' => false]);

    // Reset URL scheme to http for this test
    URL::forceScheme('http');

    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    expect(URL::formatScheme())->toBe('http://');
});

it('registers gate policies on boot', function () {
    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    expect(\Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Seminar::class))
        ->toBeInstanceOf(\App\Policies\SeminarPolicy::class);
    expect(\Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\User::class))
        ->toBeInstanceOf(\App\Policies\UserPolicy::class);
    expect(\Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Subject::class))
        ->toBeInstanceOf(\App\Policies\SubjectPolicy::class);
    expect(\Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\SeminarLocation::class))
        ->toBeInstanceOf(\App\Policies\SeminarLocationPolicy::class);
    expect(\Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Registration::class))
        ->toBeInstanceOf(\App\Policies\RegistrationPolicy::class);
});
