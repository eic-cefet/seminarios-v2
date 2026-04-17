<?php

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
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

it('registers ai rate limiter allowing 12 requests per minute', function () {
    $user = User::factory()->create();
    $request = Request::create('/api/admin/ai/transform-text', 'POST');
    $request->setUserResolver(fn () => $user);

    $callback = RateLimiter::limiter('ai');
    $result = $callback($request);

    $limit = is_array($result) ? $result[0] : $result;

    expect($limit->maxAttempts)->toBe(12);
    expect($limit->key)->toBe($user->id);
});

it('ai rate limiter falls back to IP when user is unauthenticated', function () {
    $request = Request::create('/api/admin/ai/transform-text', 'POST');
    $request->server->set('REMOTE_ADDR', '192.168.1.100');

    $callback = RateLimiter::limiter('ai');
    $result = $callback($request);

    $limit = is_array($result) ? $result[0] : $result;

    expect($limit->maxAttempts)->toBe(12);
    expect($limit->key)->toBe('192.168.1.100');
});

it('registers public rate limiter allowing 120 requests per minute keyed by user', function () {
    $user = User::factory()->create();
    $request = Request::create('/api/seminars', 'GET');
    $request->setUserResolver(fn () => $user);

    $callback = RateLimiter::limiter('public');
    $result = $callback($request);

    $limit = is_array($result) ? $result[0] : $result;

    expect($limit->maxAttempts)->toBe(120);
    expect($limit->decaySeconds)->toBe(60);
    expect($limit->key)->toBe($user->id);
});

it('public rate limiter falls back to IP when user is unauthenticated', function () {
    $request = Request::create('/api/seminars', 'GET');
    $request->server->set('REMOTE_ADDR', '192.168.1.50');

    $callback = RateLimiter::limiter('public');
    $result = $callback($request);

    $limit = is_array($result) ? $result[0] : $result;

    expect($limit->maxAttempts)->toBe(120);
    expect($limit->key)->toBe('192.168.1.50');
});

it('registers gate policies on boot', function () {
    $provider = new AppServiceProvider($this->app);
    $provider->boot();

    expect(Gate::getPolicyFor(Seminar::class))
        ->toBeInstanceOf(SeminarPolicy::class);
    expect(Gate::getPolicyFor(User::class))
        ->toBeInstanceOf(UserPolicy::class);
    expect(Gate::getPolicyFor(Subject::class))
        ->toBeInstanceOf(SubjectPolicy::class);
    expect(Gate::getPolicyFor(SeminarLocation::class))
        ->toBeInstanceOf(SeminarLocationPolicy::class);
    expect(Gate::getPolicyFor(Registration::class))
        ->toBeInstanceOf(RegistrationPolicy::class);
});
