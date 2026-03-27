<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ApiRateLimitMiddleware
 *
 * Enforces per-API-key rate limiting on /api/v1/* routes.
 * Uses CakePHP Cache (Redis in production, file-based fallback) to track
 * request counts per hour per API key prefix.
 *
 * Must be registered AFTER ApiAuthMiddleware so that the 'apiKey' request
 * attribute is available.
 */
class ApiRateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Default rate limit per hour if none is set on the API key.
     *
     * @var int
     */
    private const DEFAULT_RATE_LIMIT = 1000;

    /**
     * Cache key prefix for rate limit counters.
     *
     * @var string
     */
    private const CACHE_KEY_PREFIX = 'api_rate_';

    /**
     * Process the incoming request.
     *
     * Checks the request count for the current API key against its rate limit.
     * Returns 429 Too Many Requests if the limit is exceeded. Adds
     * X-RateLimit-Limit and X-RateLimit-Remaining headers to all API responses.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Only apply to /api/v1/* routes
        if (!str_starts_with($path, '/api/v1/')) {
            return $handler->handle($request);
        }

        $apiKey = $request->getAttribute('apiKey');
        if (!$apiKey) {
            // No API key on request — let downstream handle (ApiAuthMiddleware
            // would have returned 401 already, but be defensive).
            return $handler->handle($request);
        }

        $rateLimit = $apiKey->rate_limit ?: self::DEFAULT_RATE_LIMIT;
        $cacheKey = self::CACHE_KEY_PREFIX . $apiKey->key_prefix;

        // Read current request count from cache
        $current = Cache::read($cacheKey, 'default');
        if ($current === null) {
            $current = 0;
        }
        $current = (int)$current;

        if ($current >= $rateLimit) {
            return $this->tooManyRequests($rateLimit);
        }

        // Increment the counter. CakePHP Cache::write with 'default' config
        // uses the configured TTL. We write the incremented value; the first
        // write in a new window starts the TTL countdown.
        Cache::write($cacheKey, $current + 1, 'default');

        // Process the request
        $response = $handler->handle($request);

        // Add rate limit headers to the response
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$rateLimit)
            ->withHeader('X-RateLimit-Remaining', (string)max(0, $rateLimit - $current - 1));
    }

    /**
     * Build a 429 Too Many Requests JSON response.
     *
     * @param int $rateLimit The rate limit that was exceeded.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function tooManyRequests(int $rateLimit): ResponseInterface
    {
        $response = new Response();

        return $response
            ->withStatus(429)
            ->withType('application/json')
            ->withHeader('X-RateLimit-Limit', (string)$rateLimit)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withStringBody(json_encode([
                'error' => true,
                'message' => 'Rate limit exceeded. Maximum ' . $rateLimit . ' requests per hour.',
            ]));
    }
}
