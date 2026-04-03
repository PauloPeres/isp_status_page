<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\JwtService;
use App\Tenant\TenantContext;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JwtAuthMiddleware
 *
 * Authenticates API v2 requests using JWT Bearer tokens.
 * Only activates for /api/v2/* paths, excluding auth endpoints
 * that do not require authentication (login, refresh).
 * Sets TenantContext from the JWT org_id claim and attaches
 * the decoded payload to the request for downstream use.
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    /**
     * Paths under /api/v2/ that do NOT require JWT authentication.
     *
     * @var array<string>
     */
    private const EXCLUDED_PATHS = [
        '/api/v2/auth/login',
        '/api/v2/auth/refresh',
        '/api/v2/auth/register',
        '/api/v2/auth/oauth/exchange',
        '/api/v2/health',
        '/api/v2/health/ping',
    ];

    /**
     * Path prefixes under /api/v2/ that do NOT require JWT authentication.
     *
     * @var array<string>
     */
    private const EXCLUDED_PREFIXES = [
        '/api/v2/auth/oauth/',
        '/api/v2/public/',
    ];

    /**
     * Process the incoming request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Only apply to /api/v2/* routes
        if (!str_starts_with($path, '/api/v2/')) {
            return $handler->handle($request);
        }

        // Skip authentication for excluded paths
        foreach (self::EXCLUDED_PATHS as $excluded) {
            if ($path === $excluded) {
                return $handler->handle($request);
            }
        }

        // Skip authentication for excluded path prefixes (OAuth routes)
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        // Extract Bearer token from Authorization header
        $token = null;
        $authHeader = $request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        // Also check query param (for SSE — EventSource doesn't support custom headers)
        if (!$token) {
            $queryParams = $request->getQueryParams();
            $token = $queryParams['token'] ?? null;
        }

        if (!$token) {
            return $this->unauthorized('Missing or invalid Authorization header. Use: Bearer {token}');
        }

        // Verify JWT
        $jwtService = new JwtService();
        $payload = $jwtService->verifyAccessToken($token);

        if ($payload === null) {
            return $this->unauthorized('Invalid or expired access token');
        }

        // Check if the token has been blocked (e.g. after logout)
        $tokenId = $jwtService->getTokenIdentifier($token, $payload);
        if ($jwtService->isTokenBlocked($tokenId)) {
            return $this->unauthorized('Token has been revoked');
        }

        // Set tenant context from JWT org_id claim
        if (isset($payload->org_id) && $payload->org_id > 0) {
            TenantContext::setCurrentOrgId((int)$payload->org_id);
        }

        // Attach decoded payload to request for downstream controllers
        $request = $request->withAttribute('jwt_payload', $payload);

        return $handler->handle($request);
    }

    /**
     * Build a 401 Unauthorized JSON response.
     *
     * @param string $message The error message.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();

        return $response
            ->withStatus(401)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => $message,
            ]));
    }
}
