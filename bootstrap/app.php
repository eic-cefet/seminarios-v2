<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuditContextMiddleware;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LogRequestMiddleware;
use App\Support\Locking\LockTimeoutException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/admin.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/external.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->trustProxies(at: '*');
        $middleware->append(LogRequestMiddleware::class);
        $middleware->append(AuditContextMiddleware::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'ability' => CheckTokenAbility::class,
            'external.conditional' => \App\Http\Middleware\HandleConditionalRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle rate limiting for API routes
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiException::rateLimited()->render();
            }
        });

        // Handle validation errors for API routes
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiException::validation($e->errors())->render();
            }
        });

        $exceptions->render(function (LockTimeoutException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/api/*')) {
                return response()->json([
                    'message' => 'Sistema ocupado, tente novamente em instantes.',
                ], 503);
            }

            return null;
        });
    })->create();
