<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\TenantMiddleware;
use App\Tenant\TenantContext;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TenantMiddleware Test
 */
class TenantMiddlewareTest extends TestCase
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
    ];

    private TenantMiddleware $middleware;
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
        $this->middleware = new TenantMiddleware();
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
     * Test that public routes skip tenant resolution entirely.
     *
     * @return void
     */
    public function testPublicRoutesSkipResolution(): void
    {
        $publicPaths = [
            '/users/login',
            '/users/register',
            '/users/logout',
            '/status',
            '/status/acme',
            '/heartbeat/abc123',
            '/webhooks/stripe',
            '/api/docs',
            '/incidents/acknowledge/1/token123',
            '/registration/new',
        ];

        foreach ($publicPaths as $path) {
            TenantContext::reset();
            $request = new ServerRequest(['url' => $path]);

            $response = $this->middleware->process($request, $this->handler);

            $this->assertNull(
                TenantContext::getCurrentOrgId(),
                "TenantContext should not be set for public path: {$path}"
            );
        }
    }

    /**
     * Test subdomain-based resolution finds the correct org.
     *
     * @return void
     */
    public function testSubdomainResolution(): void
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_HOST' => 'acme-isp.statuspage.io',
            ],
        ]);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) {
                // Verify the organization attribute was set on the request
                $org = $req->getAttribute('organization');
                $this->assertNotNull($org, 'Organization attribute should be set on request');
                $this->assertEquals(1, $org->id);
                $this->assertEquals('acme-isp', $org->slug);

                return new Response();
            });

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(1, TenantContext::getCurrentOrgId());
        $this->assertTrue(TenantContext::isSet());
    }

    /**
     * Test that an invalid subdomain does not resolve.
     *
     * @return void
     */
    public function testInvalidSubdomainDoesNotResolve(): void
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_HOST' => 'nonexistent.statuspage.io',
            ],
        ]);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    /**
     * Test /api/v1/ routes are treated as public and skipped by TenantMiddleware.
     *
     * API authentication and tenant resolution for /api/v1/* is handled by
     * ApiAuthMiddleware which runs after TenantMiddleware.
     *
     * @return void
     */
    public function testApiV1RoutesSkippedByTenantMiddleware(): void
    {
        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
            'environment' => [
                'HTTP_X_ORGANIZATION_ID' => '1',
            ],
        ]);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);

        // TenantMiddleware skips /api/v1/ — it does NOT set tenant context.
        // ApiAuthMiddleware handles this downstream.
        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    /**
     * Test /api/v1/ request without org header is still passed through
     * (ApiAuthMiddleware handles auth, not TenantMiddleware).
     *
     * @return void
     */
    public function testApiV1RequestWithoutOrgPassesThrough(): void
    {
        $request = new ServerRequest([
            'url' => '/api/v1/monitors',
        ]);

        // The handler should be called since /api/v1/ is public to TenantMiddleware
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    /**
     * Test session-based resolution.
     *
     * @return void
     */
    public function testSessionResolution(): void
    {
        $request = new ServerRequest([
            'url' => '/monitors',
        ]);

        // Simulate session with current_organization_id
        $session = $request->getSession();
        $session->write('current_organization_id', 2);
        $request = $request->withAttribute('session', $session);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(2, TenantContext::getCurrentOrgId());
    }

    /**
     * Test path prefix resolution (/org/{slug}/...).
     *
     * @return void
     */
    public function testPathPrefixResolution(): void
    {
        $request = new ServerRequest([
            'url' => '/org/acme-isp/monitors',
        ]);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(1, TenantContext::getCurrentOrgId());
    }

    /**
     * Test default resolution — user with exactly one org membership.
     *
     * @return void
     */
    public function testDefaultSingleOrgUser(): void
    {
        // User 2 belongs to only org 1 (from fixtures)
        $identity = $this->createMock(\Authentication\IdentityInterface::class);
        $identity->method('getIdentifier')->willReturn(2);

        $request = new ServerRequest([
            'url' => '/monitors',
        ]);
        $request = $request->withAttribute('identity', $identity);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(1, TenantContext::getCurrentOrgId());
    }

    /**
     * Test that users with multiple orgs and no other resolution get redirected.
     *
     * @return void
     */
    public function testMultiOrgUserRedirectsToSelection(): void
    {
        // User 1 belongs to orgs 1 and 2 (from fixtures)
        $identity = $this->createMock(\Authentication\IdentityInterface::class);
        $identity->method('getIdentifier')->willReturn(1);

        $request = new ServerRequest([
            'url' => '/monitors',
        ]);
        $request = $request->withAttribute('identity', $identity);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/organizations/select', $response->getHeaderLine('Location'));
    }

    /**
     * Test that inactive org is not resolved (non-API route).
     *
     * @return void
     */
    public function testInactiveOrgIsNotResolved(): void
    {
        // Deactivate org 1
        $orgsTable = TableRegistry::getTableLocator()->get('Organizations');
        $org = $orgsTable->get(1);
        $org->active = false;
        $orgsTable->save($org);

        $request = new ServerRequest([
            'url' => '/monitors',
            'environment' => [
                'HTTP_HOST' => 'acme-isp.statuspage.io',
            ],
        ]);

        // Handler is called because no org was resolved and this is a web request
        // (unauthenticated user, non-public route — falls through to auth middleware)
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    /**
     * Test that TenantContext is reset at the start of each request.
     *
     * @return void
     */
    public function testContextResetBetweenRequests(): void
    {
        // Set some stale context
        TenantContext::setCurrentOrgId(999);

        $request = new ServerRequest([
            'url' => '/users/login',
        ]);

        $this->middleware->process($request, $this->handler);

        // After processing a public route, context should have been reset
        $this->assertNull(TenantContext::getCurrentOrgId());
    }
}
