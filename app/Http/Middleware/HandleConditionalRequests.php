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
            $lastModifiedUtc = \DateTimeImmutable::createFromInterface($lastModified)
                ->setTimezone(new \DateTimeZone('GMT'));
            $response->headers->set('Last-Modified', $lastModifiedUtc->format(\DateTimeInterface::RFC7231));
        }

        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && $ifNoneMatch !== '') {
            $tags = array_map('trim', explode(',', $ifNoneMatch));
            if (in_array($etag, $tags, true) || in_array('*', $tags, true)) {
                return $this->notModified($etag, $response->headers->get('Last-Modified'));
            }
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($lastModified instanceof \DateTimeInterface && $ifModifiedSince) {
            $clientTs = strtotime($ifModifiedSince);
            if ($clientTs !== false && $clientTs >= $lastModified->getTimestamp()) {
                return $this->notModified($etag, $response->headers->get('Last-Modified'));
            }
        }

        return $response;
    }

    /**
     * Build a 304 Not Modified response, omitting Last-Modified when unset.
     */
    private function notModified(string $etag, ?string $lastModified): Response
    {
        $headers = ['ETag' => $etag];
        if ($lastModified !== null) {
            $headers['Last-Modified'] = $lastModified;
        }

        return response('', 304, $headers);
    }
}
