<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CorsMiddleware
 *
 * Handles CORS preflight (OPTIONS) requests and adds the appropriate
 * Access-Control-* headers to all responses. Allows the APP_URL
 * origin and http://localhost:4200 for local Angular development.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Process the incoming request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        // Determine allowed origins
        $allowedOrigins = $this->getAllowedOrigins();

        // If no Origin header or origin not allowed, proceed without CORS headers
        if (empty($origin) || !in_array($origin, $allowedOrigins, true)) {
            // For OPTIONS preflight without valid origin, return 204
            if ($request->getMethod() === 'OPTIONS') {
                return (new Response())->withStatus(204);
            }

            return $handler->handle($request);
        }

        // Handle OPTIONS preflight
        if ($request->getMethod() === 'OPTIONS') {
            return (new Response())
                ->withStatus(204)
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        // Process the request and add CORS headers to the response
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Expose-Headers', 'Content-Disposition');
    }

    /**
     * Get the list of allowed origins from configuration.
     *
     * @return array<string>
     */
    private function getAllowedOrigins(): array
    {
        $origins = ['http://localhost:4200'];

        $appUrl = (string)env('APP_URL', '');
        if (!empty($appUrl)) {
            // Normalize: remove trailing slash
            $origins[] = rtrim($appUrl, '/');
        }

        return $origins;
    }
}
