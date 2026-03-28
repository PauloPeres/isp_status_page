<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Tenant\TenantContext;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * SuperAdminMiddleware
 *
 * Gates all /super-admin/* routes so that only users with is_super_admin=true
 * can access them. Resets TenantContext so super admin controllers operate
 * outside any single tenant scope.
 */
class SuperAdminMiddleware implements MiddlewareInterface
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
        $path = $request->getUri()->getPath();

        // Only activate for /super-admin paths
        if (!str_starts_with($path, '/super-admin')) {
            return $handler->handle($request);
        }

        // Reset tenant context — super admin operates globally
        TenantContext::reset();

        // Check authentication
        $identity = $request->getAttribute('identity');
        if (!$identity) {
            return $this->forbidden($request, 'Authentication required.');
        }

        // Check super admin flag
        $isSuperAdmin = (bool)($identity->get('is_super_admin') ?? false);
        if (!$isSuperAdmin) {
            return $this->forbidden($request, 'Super admin access required.');
        }

        return $handler->handle($request);
    }

    /**
     * Return a 403 response. JSON for API-like requests, HTML for web requests.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param string $message The error message.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function forbidden(ServerRequestInterface $request, string $message): ResponseInterface
    {
        $response = new Response();

        // Detect API-like requests (Accept: application/json or XHR)
        $accept = $request->getHeaderLine('Accept');
        $isXhr = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if ($isXhr || str_contains($accept, 'application/json')) {
            return $response->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => $message,
                    'status' => 403,
                ]));
        }

        // Web request — render a simple forbidden page
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
        .container { text-align: center; padding: 2rem; }
        h1 { font-size: 4rem; color: #e94560; margin: 0; }
        p { font-size: 1.2rem; color: #666; }
        a { color: #1E88E5; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>403</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <p><a href="/">Return to Home</a></p>
    </div>
</body>
</html>';

        return $response->withStatus(403)
            ->withType('text/html')
            ->withStringBody($html);
    }
}
