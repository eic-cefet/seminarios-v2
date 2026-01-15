<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasAnyRole(['admin', 'teacher'])) {
            if ($request->expectsJson() || $request->is('api/*')) {
                throw ApiException::forbidden();
            }

            return redirect('/');
        }

        return $next($request);
    }
}
