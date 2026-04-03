<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PageCacheMiddleware
 *
 * Redis-based full-page cache for public/static pages.
 * Intercepts GET requests to configured URL patterns, returns cached
 * responses on HIT, and stores fresh responses on MISS with per-route TTLs.
 *
 * Skips caching for:
 * - Non-GET requests
 * - Authenticated requests (Authorization header or session cookie)
 * - Non-200 responses
 *
 * Adds X-Cache header (HIT/MISS) for debugging.
 */
class PageCacheMiddleware implements MiddlewareInterface
{
    /**
     * Cache key prefix in Redis.
     */
    private const CACHE_PREFIX = 'page_cache:';

    /**
     * URL pattern => TTL in seconds.
     * Patterns ending with /* are treated as prefix matches.
     *
     * @var array<string, int>
     */
    private array $cacheRules = [
        '/status' => 30,            // public status page
        '/status/history' => 60,    // status history
        '/' => 300,                 // landing page
        '/about' => 3600,
        '/terms' => 3600,
        '/privacy' => 3600,
        '/blog' => 600,
        '/blog/*' => 600,
        '/api/v2/public/*' => 30,   // public status API
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
        // Skip caching entirely during tests (CakePHP IntegrationTestTrait)
        if (PHP_SAPI === 'cli' || defined('PHPUNIT_RUNNING')) {
            return $handler->handle($request);
        }

        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }

        // Skip cache for authenticated requests
        if ($this->isAuthenticated($request)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        $ttl = $this->matchRoute($path);

        // Not a cacheable route
        if ($ttl === null) {
            return $handler->handle($request);
        }

        $redis = $this->getRedisConnection();
        if ($redis === null) {
            // Redis unavailable — proceed without cache
            return $handler->handle($request);
        }

        // Build cache key from the full URL (path + query string)
        $fullUrl = $path;
        $query = $request->getUri()->getQuery();
        if ($query !== '') {
            $fullUrl .= '?' . $query;
        }
        $cacheKey = self::CACHE_PREFIX . md5($fullUrl);

        // Try cache HIT
        try {
            $cached = $redis->get($cacheKey);
            if ($cached !== false && $cached !== '') {
                $data = json_decode($cached, true);
                if (is_array($data) && isset($data['body'])) {
                    $response = new Response();

                    return $response
                        ->withStatus(200)
                        ->withType($data['content_type'] ?? 'text/html')
                        ->withStringBody($data['body'])
                        ->withHeader('X-Cache', 'HIT');
                }
            }
        } catch (\Throwable $e) {
            Log::warning("PageCacheMiddleware: Redis read failed — {$e->getMessage()}");
        }

        // Cache MISS — proceed with request
        $response = $handler->handle($request);

        // Only cache 200 responses
        if ($response->getStatusCode() === 200) {
            try {
                $body = (string)$response->getBody();
                $contentType = $response->getHeaderLine('Content-Type') ?: 'text/html';

                $cacheData = json_encode([
                    'body' => $body,
                    'content_type' => $contentType,
                ]);

                if ($cacheData !== false) {
                    $redis->setex($cacheKey, $ttl, $cacheData);
                }
            } catch (\Throwable $e) {
                Log::warning("PageCacheMiddleware: Redis write failed — {$e->getMessage()}");
            }
        }

        return $response->withHeader('X-Cache', 'MISS');
    }

    /**
     * Check if the request appears to be from an authenticated user.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return bool
     */
    private function isAuthenticated(ServerRequestInterface $request): bool
    {
        // Check Authorization header (JWT / Bearer tokens)
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader !== '') {
            return true;
        }

        // Check for CakePHP session cookie (indicates a logged-in web user)
        $cookies = $request->getCookieParams();
        if (!empty($cookies['csrfToken']) && !empty($cookies['CAKEPHP'])) {
            return true;
        }

        return false;
    }

    /**
     * Match the request path against cache rules and return the TTL if matched.
     *
     * @param string $path The request path.
     * @return int|null TTL in seconds, or null if no rule matches.
     */
    private function matchRoute(string $path): ?int
    {
        // Normalize path — remove trailing slash except for root
        $normalizedPath = $path !== '/' ? rtrim($path, '/') : $path;

        // Exact match first
        if (isset($this->cacheRules[$normalizedPath])) {
            return $this->cacheRules[$normalizedPath];
        }

        // Wildcard match (patterns ending with /*)
        foreach ($this->cacheRules as $pattern => $ttl) {
            if (str_ends_with($pattern, '/*')) {
                $prefix = substr($pattern, 0, -1); // e.g. '/blog/' from '/blog/*'
                if (str_starts_with($normalizedPath, $prefix)) {
                    return $ttl;
                }
            }
        }

        return null;
    }

    /**
     * Get a Redis connection using the same REDIS_URL environment variable.
     *
     * @return \Redis|null
     */
    private function getRedisConnection(): ?\Redis
    {
        static $redis = null;

        if ($redis !== null) {
            try {
                $redis->ping();

                return $redis;
            } catch (\Throwable $e) {
                $redis = null;
            }
        }

        try {
            $redis = new \Redis();

            $redisUrl = getenv('REDIS_URL') ?: '';
            $host = '127.0.0.1';
            $port = 6379;
            $password = '';

            if ($redisUrl) {
                $parsed = parse_url($redisUrl);
                $host = $parsed['host'] ?? '127.0.0.1';
                $port = $parsed['port'] ?? 6379;
                $password = $parsed['pass'] ?? '';
            }

            $redis->connect($host, $port, 2.0);

            if ($password !== '') {
                $redis->auth($password);
            }

            // Use DB 7 for page cache (separate from other uses)
            $redis->select(7);

            return $redis;
        } catch (\Throwable $e) {
            Log::warning("PageCacheMiddleware: Redis connection failed — {$e->getMessage()}");
            $redis = null;

            return null;
        }
    }
}
