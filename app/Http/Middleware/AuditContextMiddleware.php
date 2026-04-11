<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $action = $request->route()?->getActionName();

        if ($action && $action !== 'Closure') {
            $origin = class_basename(Str::before($action, '@')).'@'.Str::after($action, '@');
            Context::add('audit.origin', $origin);
        }

        Context::add('audit.ip', $request->ip());

        return $next($request);
    }
}
