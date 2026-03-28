<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * SecurityHeadersMiddleware
 *
 * Adds standard security headers to all responses to mitigate
 * common web vulnerabilities (TASK-AUTH-008).
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and add security headers to the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
