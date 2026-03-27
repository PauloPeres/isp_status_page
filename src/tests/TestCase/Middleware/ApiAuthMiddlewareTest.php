<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\ApiAuthMiddleware;
use App\Model\Entity\ApiKey;
use App\Service\ApiKeyService;
use App\Tenant\TenantContext;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ApiAuthMiddleware Test
 *
 * Tests authentication for /api/v1/* routes using API keys.
 */
class ApiAuthMiddlewareTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.ApiKeys',
    ];

    private ApiAuthMiddleware $middleware;
    private RequestHandlerInterface $handler;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
        $this->middleware = new ApiAuthMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->method('handle')
            ->willReturn(new Response());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    /**
     * Test that non-API routes pass through without authentication.
     *
     * @return void
     */
    public function testNonApiRoutesPassThrough(): void
    {
        $nonApiPaths = [
            '/monitors',
            '/dashboard',
            '/users/login',
            '/api/docs',
            '/status',
        ];

        foreach ($nonApiPaths as $path) {
            $request = new ServerRequest(['url' => $path]);

            $this->handler = $this->createMock(RequestHandlerInterface::class);
            $this->handler->expects($this->once())
                ->method('handle')
                ->willReturn(new Response());

            $response = $this->middleware->process($request, $this->handler);

            $this->assertEquals(200, $response->getStatusCode(), "Non-API path {$path} should pass through");
        }
    }

    /**
     * Test that API request without Authorization header returns 401.
     *
     * @return void
     */
    public function testMissingAuthHeaderReturns401(): void
    {
        $request = new ServerRequest(['url' => '/api/v1/monitors']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Authorization header', $body['message']);
    }

    /**
     * Test that API request with non-Bearer auth returns 401.
     *
     * @return void
     */
    public function testNonBearerAuthReturns401(): void
    {
        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
            'environment' => [
                'HTTP_AUTHORIZATION' => 'Basic dXNlcjpwYXNz',
            ],
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test that API request with invalid token returns 401.
     *
     * @return void
     */
    public function testInvalidTokenReturns401(): void
    {
        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
            'environment' => [
                'HTTP_AUTHORIZATION' => 'Bearer test_fake_key_not_a_real_token_00000000000000000000',
            ],
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('Invalid or expired', $body['message']);
    }

    /**
     * Test that a valid API key sets tenant context and request attributes.
     *
     * @return void
     */
    public function testValidTokenSetsTenantContextAndAttributes(): void
    {
        // Generate a real API key for testing
        $apiKeyService = new ApiKeyService();
        $result = $apiKeyService->generate(
            1,
            1,
            'Test Key',
            ['read', 'write'],
            1000,
            null
        );
        $plainKey = $result['key'];

        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
            'environment' => [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $plainKey,
            ],
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                // Verify the request attributes were set
                $apiKey = $req->getAttribute('apiKey');
                $this->assertInstanceOf(ApiKey::class, $apiKey);
                $this->assertEquals(1, $apiKey->organization_id);

                $permissions = $req->getAttribute('apiKeyPermissions');
                $this->assertIsArray($permissions);
                $this->assertContains('read', $permissions);
                $this->assertContains('write', $permissions);

                return new Response();
            });

        $this->middleware->process($request, $handler);

        // Verify tenant context was set
        $this->assertEquals(1, TenantContext::getCurrentOrgId());
    }

    /**
     * Test that 401 response has correct content type.
     *
     * @return void
     */
    public function testUnauthorizedResponseIsJson(): void
    {
        $request = new ServerRequest(['url' => '/api/v1/monitors']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Test that empty Bearer token returns 401.
     *
     * @return void
     */
    public function testEmptyBearerTokenReturns401(): void
    {
        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
            'environment' => [
                'HTTP_AUTHORIZATION' => 'Bearer ',
            ],
        ]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
