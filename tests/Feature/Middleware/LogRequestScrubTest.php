<?php

use App\Http\Middleware\LogRequestMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

it('redacts sensitive keys in request log context', function () {
    config()->set('logging.enable_request_logging', true);

    $captured = [];
    Log::listen(function ($message) use (&$captured) {
        $captured[] = $message->context;
    });

    $middleware = app(LogRequestMiddleware::class);
    $request = Request::create(
        '/api/anything?email=leak@example.com&token=abc&foo=bar',
        'GET',
    );
    $response = new Response('ok', 200);

    $middleware->handle($request, fn ($r) => $response);
    $middleware->terminate($request, $response);

    $ctx = end($captured);
    expect($ctx)->not->toBeFalse();

    $serialized = json_encode($ctx);
    expect($serialized)->not->toContain('leak@example.com')
        ->and($serialized)->not->toContain('abc');

    expect($ctx['query_params']['email'])->toBe('[redacted]')
        ->and($ctx['query_params']['token'])->toBe('[redacted]')
        ->and($ctx['query_params']['foo'])->toBe('bar');
});

it('does not touch routes without sensitive params', function () {
    config()->set('logging.enable_request_logging', true);

    $captured = [];
    Log::listen(function ($message) use (&$captured) {
        $captured[] = $message->context;
    });

    $middleware = app(LogRequestMiddleware::class);
    $request = Request::create('/api/ok?foo=bar', 'GET');
    $response = new Response('ok', 200);

    $middleware->handle($request, fn ($r) => $response);
    $middleware->terminate($request, $response);

    $ctx = end($captured);
    expect($ctx['query_params']['foo'])->toBe('bar');
});
