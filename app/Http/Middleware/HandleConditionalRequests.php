<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleConditionalRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request->method() !== 'GET' || $response->getStatusCode() !== 200) {
            return $response;
        }

        $body = $response->getContent();
        $etag = 'W/"'.substr(hash('sha256', (string) $body), 0, 16).'"';
        $response->headers->set('ETag', $etag);

        $lastModified = $request->attributes->get('external_last_modified');
        if ($lastModified instanceof \DateTimeInterface) {
            $response->headers->set('Last-Modified', $lastModified->format(\DateTimeInterface::RFC7231));
        }

        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Last-Modified' => $response->headers->get('Last-Modified'),
            ]);
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($lastModified instanceof \DateTimeInterface && $ifModifiedSince) {
            $clientTs = strtotime($ifModifiedSince);
            if ($clientTs !== false && $clientTs >= $lastModified->getTimestamp()) {
                return response('', 304, [
                    'ETag' => $etag,
                    'Last-Modified' => $response->headers->get('Last-Modified'),
                ]);
            }
        }

        return $response;
    }
}
