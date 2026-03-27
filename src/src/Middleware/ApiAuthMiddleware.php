<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\ApiKeyService;
use App\Tenant\TenantContext;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ApiAuthMiddleware
 *
 * Authenticates API requests to /api/v1/* routes using Bearer token (API key).
 * Validates the key via ApiKeyService, sets the tenant context from the key's
 * organization, and attaches the API key entity + permissions to the request
 * for downstream use by controllers.
 */
class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Process the incoming request.
     *
     * Only activates for /api/v1/* paths. Extracts the Bearer token from
     * the Authorization header, validates it, sets TenantContext, and
     * attaches the API key info to the request attributes.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Only apply to /api/v1/* routes (skip /api/docs and other non-v1 paths)
        if (!str_starts_with($path, '/api/v1/')) {
            return $handler->handle($request);
        }

        // Extract Bearer token from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header. Use: Bearer sk_live_...');
        }

        $token = substr($authHeader, 7);

        // Validate the API key
        $apiKeyService = new ApiKeyService();
        $apiKey = $apiKeyService->validate($token);

        if (!$apiKey) {
            return $this->unauthorized('Invalid or expired API key');
        }

        // Set tenant context from the API key's organization
        TenantContext::setCurrentOrgId($apiKey->organization_id);

        // Attach API key info to request for downstream use
        $request = $request->withAttribute('apiKey', $apiKey);
        $request = $request->withAttribute('apiKeyPermissions', $apiKey->getPermissions());

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
                'error' => true,
                'message' => $message,
            ]));
    }
}
