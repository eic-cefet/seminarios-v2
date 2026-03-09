<?php

use App\Http\Middleware\AuditContextMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

describe('AuditContextMiddleware', function () {
    it('sets audit origin from controller action', function () {
        $middleware = new AuditContextMiddleware;

        $request = Request::create('/test', 'GET');
        $route = new \Illuminate\Routing\Route('GET', '/test', []);
        $route->setAction(['uses' => 'App\Http\Controllers\Admin\AdminSeminarController@index', 'controller' => 'App\Http\Controllers\Admin\AdminSeminarController@index']);
        $request->setRouteResolver(fn () => $route);

        $middleware->handle($request, fn () => response('ok'));

        expect(Context::get('audit.origin'))->toBe('AdminSeminarController@index');
        expect(Context::get('audit.ip'))->toBe('127.0.0.1');
    });

    it('sets only ip for closure routes', function () {
        $middleware = new AuditContextMiddleware;

        $request = Request::create('/test', 'GET');
        $route = new \Illuminate\Routing\Route('GET', '/test', function () {
            return response('ok');
        });
        $request->setRouteResolver(fn () => $route);

        $middleware->handle($request, fn () => response('ok'));

        expect(Context::get('audit.origin'))->toBeNull();
        expect(Context::get('audit.ip'))->toBe('127.0.0.1');
    });

    it('handles request without route', function () {
        $middleware = new AuditContextMiddleware;

        $request = Request::create('/test', 'GET');

        $middleware->handle($request, fn () => response('ok'));

        expect(Context::get('audit.origin'))->toBeNull();
        expect(Context::get('audit.ip'))->toBe('127.0.0.1');
    });
});
