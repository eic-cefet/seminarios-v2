<?php

use App\Http\Middleware\HandleConditionalRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('omits Last-Modified header on 304 when controller did not set external_last_modified', function () {
    $middleware = new HandleConditionalRequests;

    $body = '{"data":[]}';
    $etag = 'W/"'.substr(hash('sha256', $body), 0, 16).'"';

    $request = Request::create('/api/external/v1/whatever', 'GET');
    $request->headers->set('If-None-Match', $etag);

    $response = $middleware->handle($request, function () use ($body): Response {
        return new Response($body, 200);
    });

    expect($response->getStatusCode())->toBe(304);
    expect($response->headers->get('ETag'))->toBe($etag);
    expect($response->headers->has('Last-Modified'))->toBeFalse();
});
