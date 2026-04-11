<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        // Session-based auth has no token abilities to check
        if (! $user?->currentAccessToken() || ! method_exists($user->currentAccessToken(), 'can')) {
            return $next($request);
        }

        if ($user->tokenCan('*') || $user->tokenCan($ability)) {
            return $next($request);
        }

        throw ApiException::forbidden("Token missing required ability: {$ability}");
    }
}
