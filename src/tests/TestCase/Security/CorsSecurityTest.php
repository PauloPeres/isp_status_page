<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression tests for CorsMiddleware.
 *
 * Verifies CORS configuration security at the source-code level:
 * no wildcard origins, allowlist validation, and preflight handling.
 */
class CorsSecurityTest extends TestCase
{
    /**
     * Cached source code of CorsMiddleware.
     */
    private string $source;

    public function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(ROOT . '/src/Middleware/CorsMiddleware.php');
    }

    /**
     * Verify the CORS middleware does NOT use a wildcard Access-Control-Allow-Origin.
     * Wildcard origins are insecure when credentials (cookies/auth headers) are used.
     */
    public function testCorsDoesNotUseWildcard(): void
    {
        // Must not set Access-Control-Allow-Origin to literal '*'
        $this->assertStringNotContainsString(
            "Access-Control-Allow-Origin', '*'",
            $this->source,
            'CorsMiddleware must not use wildcard (*) for Access-Control-Allow-Origin'
        );

        // Double-check with double quotes
        $this->assertStringNotContainsString(
            'Access-Control-Allow-Origin", "*"',
            $this->source,
            'CorsMiddleware must not use wildcard (*) for Access-Control-Allow-Origin'
        );

        // The middleware reflects the validated $origin, not a wildcard
        $this->assertStringContainsString(
            "Access-Control-Allow-Origin', \$origin",
            $this->source,
            'CorsMiddleware must reflect the validated origin, not a wildcard'
        );
    }

    /**
     * Verify the CORS middleware validates the Origin header against an allowlist.
     */
    public function testCorsValidatesOrigin(): void
    {
        // Must have an allowlist mechanism
        $this->assertStringContainsString(
            'getAllowedOrigins',
            $this->source,
            'CorsMiddleware must have a method to get allowed origins'
        );

        // Must check the origin against the allowlist using in_array with strict comparison
        $this->assertStringContainsString(
            'in_array($origin, $allowedOrigins, true)',
            $this->source,
            'CorsMiddleware must validate origin against allowlist with strict comparison'
        );

        // Must reference APP_URL for dynamic origin configuration
        $this->assertStringContainsString(
            'APP_URL',
            $this->source,
            'CorsMiddleware must use APP_URL environment variable for allowed origins'
        );
    }

    /**
     * Verify the CORS middleware properly handles OPTIONS preflight requests.
     */
    public function testCorsHandlesPreflight(): void
    {
        // Must check for OPTIONS method
        $this->assertStringContainsString(
            "'OPTIONS'",
            $this->source,
            'CorsMiddleware must check for OPTIONS method for preflight handling'
        );

        // Must set Access-Control-Allow-Methods
        $this->assertStringContainsString(
            'Access-Control-Allow-Methods',
            $this->source,
            'CorsMiddleware must set Access-Control-Allow-Methods for preflight responses'
        );

        // Must set Access-Control-Allow-Headers
        $this->assertStringContainsString(
            'Access-Control-Allow-Headers',
            $this->source,
            'CorsMiddleware must set Access-Control-Allow-Headers for preflight responses'
        );

        // Must include Authorization in allowed headers (for JWT)
        $this->assertStringContainsString(
            'Authorization',
            $this->source,
            'CorsMiddleware must allow Authorization header for JWT authentication'
        );

        // Must return 204 for preflight
        $this->assertStringContainsString(
            'withStatus(204)',
            $this->source,
            'CorsMiddleware must return HTTP 204 for preflight responses'
        );
    }
}
