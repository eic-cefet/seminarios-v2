<?php

use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Enable request logging for these specific tests
    config(['logging.enable_request_logging' => true]);

    // Clear the default log file before each test
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
    }
});

it('logs GET requests with correct context', function () {
    $this->get('/')->assertSuccessful();

    $logPath = storage_path('logs/laravel.log');
    expect(file_exists($logPath))->toBeTrue();

    $logContent = file_get_contents($logPath);
    expect($logContent)
        ->toContain('GET /')
        ->toContain('"method":"GET"')
        ->toContain('"path":"/"')
        ->toContain('"response_status":200')
        ->toContain('"duration_ms":');
});

it('logs POST requests with request size', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $logPath = storage_path('logs/laravel.log');
    $logContent = file_get_contents($logPath);

    expect($logContent)
        ->toContain('POST api/auth/login')
        ->toContain('"method":"POST"')
        ->toContain('"request_size":');
});

it('logs query parameters', function () {
    $this->get('/?foo=bar&baz=qux');

    $logPath = storage_path('logs/laravel.log');
    $logContent = file_get_contents($logPath);

    expect($logContent)
        ->toContain('"query_params":{"foo":"bar","baz":"qux"}');
});

it('logs authenticated user id', function () {
    $user = actingAsUser();

    $this->get('/api/user');

    $logPath = storage_path('logs/laravel.log');
    $logContent = file_get_contents($logPath);

    expect($logContent)
        ->toContain('"user_id":'.$user->id);
});

it('filters sensitive headers from logs', function () {
    $this->withHeaders([
        'Authorization' => 'Bearer secret-token',
        'X-CSRF-TOKEN' => 'csrf-token',
        'X-Custom-Header' => 'custom-value',
    ])->get('/');

    $logPath = storage_path('logs/laravel.log');
    $logContent = file_get_contents($logPath);

    expect($logContent)
        ->not->toContain('secret-token')
        ->not->toContain('"authorization"')
        ->toContain('x-custom-header');
});

it('logs response duration in milliseconds', function () {
    $this->get('/');

    $logPath = storage_path('logs/laravel.log');
    $logContent = file_get_contents($logPath);

    expect($logContent)
        ->toContain('"duration_ms":');
});

it('captures response size when Content-Length header is present', function () {
    $middleware = new \App\Http\Middleware\LogRequestMiddleware;
    $request = \Illuminate\Http\Request::create('/test', 'GET');

    $response = new \Symfony\Component\HttpFoundation\Response('test content');
    $response->headers->set('Content-Length', '12');

    $middleware->handle($request, fn () => $response);

    expect($request->attributes->get('_log_response_size'))->toBe(12);
    expect($request->attributes->get('_log_response_status'))->toBe(200);
});

it('skips logging when disabled via config', function () {
    config(['logging.enable_request_logging' => false]);

    $logPath = storage_path('logs/laravel.log');
    file_put_contents($logPath, '');

    $this->get('/');

    $logContent = file_get_contents($logPath);

    expect($logContent)->toBeEmpty();
});

it('skips logging when start time is not set', function () {
    $middleware = new \App\Http\Middleware\LogRequestMiddleware;
    $request = new \Illuminate\Http\Request;
    $response = new \Symfony\Component\HttpFoundation\Response;

    // Call terminate without handle (no start time set)
    $middleware->terminate($request, $response);

    // Should not throw and should return early
    expect(true)->toBeTrue();
});
