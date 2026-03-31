<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression tests for JwtAuthMiddleware.
 *
 * Verifies JWT middleware security patterns at the source-code level:
 * header parsing, route exclusions, algorithm pinning, expiry checks,
 * and request attribute injection.
 */
class JwtMiddlewareTest extends TestCase
{
    /**
     * Cached source code of JwtAuthMiddleware.
     */
    private string $source;

    /**
     * Cached source code of JwtService.
     */
    private string $jwtServiceSource;

    public function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(ROOT . '/src/Middleware/JwtAuthMiddleware.php');
        $this->jwtServiceSource = file_get_contents(ROOT . '/src/Service/JwtService.php');
    }

    /**
     * Verify the middleware parses the Bearer token from the Authorization header.
     */
    public function testMiddlewareChecksAuthorizationHeader(): void
    {
        $this->assertStringContainsString(
            'getHeaderLine(\'Authorization\')',
            $this->source,
            'JwtAuthMiddleware must read the Authorization header'
        );

        $this->assertStringContainsString(
            "str_starts_with(\$authHeader, 'Bearer ')",
            $this->source,
            'JwtAuthMiddleware must check for Bearer prefix'
        );

        $this->assertStringContainsString(
            'substr($authHeader, 7)',
            $this->source,
            'JwtAuthMiddleware must extract token after "Bearer "'
        );
    }

    /**
     * Verify the middleware skips authentication for login, register, and refresh routes.
     */
    public function testMiddlewareSkipsAuthRoutes(): void
    {
        $this->assertStringContainsString(
            '/api/v2/auth/login',
            $this->source,
            'JwtAuthMiddleware must exclude /api/v2/auth/login from authentication'
        );

        $this->assertStringContainsString(
            '/api/v2/auth/register',
            $this->source,
            'JwtAuthMiddleware must exclude /api/v2/auth/register from authentication'
        );

        $this->assertStringContainsString(
            '/api/v2/auth/refresh',
            $this->source,
            'JwtAuthMiddleware must exclude /api/v2/auth/refresh from authentication'
        );

        // Verify these are in the EXCLUDED_PATHS constant
        $this->assertStringContainsString(
            'EXCLUDED_PATHS',
            $this->source,
            'JwtAuthMiddleware must define excluded paths in a constant'
        );
    }

    /**
     * Verify the JwtService explicitly specifies HS256 algorithm when decoding
     * to prevent algorithm confusion attacks (e.g., switching to 'none' or RS256).
     */
    public function testMiddlewareDecodesWithExplicitAlgorithm(): void
    {
        // The Key class constructor requires an explicit algorithm
        $this->assertStringContainsString(
            "new Key(\$this->secretKey, \$this->algorithm)",
            $this->jwtServiceSource,
            'JwtService must use an explicit Key with algorithm for JWT::decode()'
        );

        // Verify the algorithm is pinned to HS256
        $this->assertStringContainsString(
            "'HS256'",
            $this->jwtServiceSource,
            'JwtService must pin algorithm to HS256'
        );

        // Verify it uses Firebase JWT Key object (not raw array of algorithms)
        $this->assertStringContainsString(
            'use Firebase\JWT\Key',
            $this->jwtServiceSource,
            'JwtService must use Firebase\\JWT\\Key for algorithm pinning'
        );
    }

    /**
     * Verify that JWT decode inherently checks token expiry via the 'exp' claim.
     * Firebase JWT library automatically rejects expired tokens when exp is set.
     */
    public function testMiddlewareRejectsExpiredTokens(): void
    {
        // Access tokens must include an 'exp' claim
        $this->assertStringContainsString(
            "'exp' => time() + \$this->accessTokenTtl",
            $this->jwtServiceSource,
            'JwtService must set exp claim in access tokens'
        );

        // The middleware must check verifyAccessToken result for null (expired/invalid)
        $this->assertStringContainsString(
            '$payload === null',
            $this->source,
            'JwtAuthMiddleware must check for null payload (expired or invalid token)'
        );

        // Must return 401 on invalid/expired token
        $this->assertStringContainsString(
            'Invalid or expired access token',
            $this->source,
            'JwtAuthMiddleware must return appropriate error for expired tokens'
        );
    }

    /**
     * Verify the middleware sets the decoded JWT payload as a request attribute
     * so downstream controllers can access user identity.
     */
    public function testMiddlewareSetsUserAttributes(): void
    {
        $this->assertStringContainsString(
            "withAttribute('jwt_payload', \$payload)",
            $this->source,
            'JwtAuthMiddleware must attach decoded JWT payload to request as jwt_payload attribute'
        );
    }
}
