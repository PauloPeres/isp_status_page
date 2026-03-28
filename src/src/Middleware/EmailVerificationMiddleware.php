<?php
declare(strict_types=1);

namespace App\Middleware;

use Authentication\IdentityInterface;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * EmailVerificationMiddleware
 *
 * After authentication, checks if the logged-in user has email_verified = false.
 * If so, redirects to /verify-email except for whitelisted routes.
 * This gently guides unverified users to verify without locking them out entirely.
 *
 * (TASK-AUTH-017)
 */
class EmailVerificationMiddleware implements MiddlewareInterface
{
    /**
     * Routes that unverified users are allowed to access.
     *
     * @var array<string>
     */
    private const ALLOWED_PATHS = [
        '/verify-email',
        '/resend-verification',
        '/users/logout',
        '/users/login',
    ];

    /**
     * Process the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $request->getAttribute('identity');

        // No authenticated user — let the request proceed (authentication middleware handles redirects)
        if (!$identity) {
            return $handler->handle($request);
        }

        // Check if the user has email_verified = false
        $emailVerified = true;
        if ($identity instanceof IdentityInterface) {
            $data = $identity->getOriginalData();
            if (is_object($data) && isset($data->email_verified)) {
                $emailVerified = (bool)$data->email_verified;
            } elseif (is_array($data) && isset($data['email_verified'])) {
                $emailVerified = (bool)$data['email_verified'];
            }
        }

        if ($emailVerified) {
            return $handler->handle($request);
        }

        // User is not verified — check if the current path is allowed
        $path = $request->getUri()->getPath();

        foreach (self::ALLOWED_PATHS as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return $handler->handle($request);
            }
        }

        // Also allow API routes and static assets
        if (str_starts_with($path, '/api/') || str_starts_with($path, '/auth/')) {
            return $handler->handle($request);
        }

        // Redirect unverified user to verification page
        $response = new Response();

        return $response
            ->withHeader('Location', '/verify-email')
            ->withStatus(302);
    }
}
