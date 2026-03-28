<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Tenant\TenantContext;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TenantMiddleware
 *
 * Determines the current organization for each request and populates TenantContext.
 *
 * Resolution order:
 * 1. API requests — X-Organization-Id header (API key lookup to be added later)
 * 2. Subdomain — e.g. acme.statuspage.io -> org with slug "acme"
 * 3. Session — current_organization_id stored during org switch
 * 4. Path prefix — /org/{slug}/... (dev fallback)
 * 5. Default — if authenticated user belongs to exactly one org, use that
 */
class TenantMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    /**
     * Path prefixes that skip tenant resolution entirely.
     *
     * @var array<string>
     */
    private array $publicPaths = [
        '/users/login',
        '/users/register',
        '/users/logout',
        '/register',
        '/verify-email',
        '/registration/',
        '/status',
        '/heartbeat/',
        '/webhooks/',
        '/api/v1/',
        '/api/docs',
        '/incidents/acknowledge/',
        '/auth/',
        '/two-factor/',
        '/organizations/select',
        '/organizations/switch',
        '/onboarding/',
        '/s/',
        '/super-admin',
    ];

    /**
     * Base domains that can carry subdomains.
     * Only the first subdomain segment is used as the org slug.
     *
     * @var array<string>
     */
    private array $baseDomains = [
        'statuspage.io',
        'statuspage.local',
        'localhost',
    ];

    /**
     * Process the incoming request and resolve tenant context.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Reset tenant context for each request
        TenantContext::reset();

        $path = $request->getUri()->getPath();

        // Skip for public routes
        if ($this->isPublicRoute($path)) {
            return $handler->handle($request);
        }

        $orgId = $this->resolveOrganization($request);

        if ($orgId) {
            $org = $this->loadOrganization($orgId);
            if ($org) {
                TenantContext::setCurrentOrgId($org->id);
                TenantContext::setCurrentOrganization($org->toArray());
                $request = $request->withAttribute('organization', $org);

                return $handler->handle($request);
            }
        }

        // No org resolved — check if this is an API request
        if ($this->isApiRequest($path)) {
            $response = new Response();

            return $response->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'Organization could not be determined. Provide X-Organization-Id header.',
                ]));
        }

        // For web requests: if user is authenticated, redirect to org selection
        $identity = $request->getAttribute('identity');
        if ($identity) {
            $response = new Response();

            return $response->withStatus(302)
                ->withHeader('Location', '/organizations/select');
        }

        // Unauthenticated, non-public route — let auth middleware handle redirect
        return $handler->handle($request);
    }

    /**
     * Determine whether the given path matches a public (tenant-free) route.
     *
     * @param string $path The request path.
     * @return bool
     */
    private function isPublicRoute(string $path): bool
    {
        foreach ($this->publicPaths as $publicPath) {
            if ($path === $publicPath || str_starts_with($path, $publicPath . '/') || str_starts_with($path, $publicPath . '?')) {
                return true;
            }
            // Also match if the public path ends with '/' (prefix-style match)
            if (str_ends_with($publicPath, '/') && str_starts_with($path, $publicPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the request targets the API namespace.
     *
     * @param string $path The request path.
     * @return bool
     */
    private function isApiRequest(string $path): bool
    {
        return str_starts_with($path, '/api/');
    }

    /**
     * Walk through the resolution chain and return the first org ID found.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveOrganization(ServerRequestInterface $request): ?int
    {
        $path = $request->getUri()->getPath();

        // 1. API requests — header-based resolution
        if ($this->isApiRequest($path)) {
            $orgId = $this->resolveFromApiHeader($request);
            if ($orgId) {
                return $orgId;
            }
        }

        // 2. Subdomain
        $orgId = $this->resolveFromSubdomain($request);
        if ($orgId) {
            return $orgId;
        }

        // 2.5. Impersonation — super admin overriding tenant context (TASK-SA-010)
        $orgId = $this->resolveFromImpersonation($request);
        if ($orgId) {
            return $orgId;
        }

        // 3. Session
        $orgId = $this->resolveFromSession($request);
        if ($orgId) {
            return $orgId;
        }

        // 4. Path prefix (/org/{slug}/...)
        $orgId = $this->resolveFromPathPrefix($path);
        if ($orgId) {
            return $orgId;
        }

        // 5. Default — single-org user
        $orgId = $this->resolveFromUserDefault($request);
        if ($orgId) {
            return $orgId;
        }

        return null;
    }

    /**
     * Resolve org from X-Organization-Id header (API requests).
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveFromApiHeader(ServerRequestInterface $request): ?int
    {
        $headerValue = $request->getHeaderLine('X-Organization-Id');
        if ($headerValue !== '' && ctype_digit($headerValue)) {
            return (int)$headerValue;
        }

        return null;
    }

    /**
     * Resolve org from the subdomain portion of the host.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveFromSubdomain(ServerRequestInterface $request): ?int
    {
        $host = $request->getUri()->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain === null) {
            return null;
        }

        $orgsTable = $this->fetchTable('Organizations');
        $org = $orgsTable->find()
            ->where(['slug' => $subdomain, 'active' => true])
            ->first();

        return $org ? $org->id : null;
    }

    /**
     * Extract the subdomain from a hostname, given the known base domains.
     *
     * @param string $host The full hostname.
     * @return string|null The subdomain slug or null.
     */
    private function extractSubdomain(string $host): ?string
    {
        // Remove port if present
        $host = strtolower(preg_replace('/:\d+$/', '', $host));

        foreach ($this->baseDomains as $baseDomain) {
            $suffix = '.' . $baseDomain;
            if (str_ends_with($host, $suffix)) {
                $subdomain = substr($host, 0, -strlen($suffix));
                // Only accept single-segment subdomains (no dots)
                if ($subdomain !== '' && !str_contains($subdomain, '.')) {
                    return $subdomain;
                }
            }
        }

        return null;
    }

    /**
     * Resolve org from super admin impersonation session (TASK-SA-010).
     *
     * Only honoured when the authenticated user has is_super_admin = true.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveFromImpersonation(ServerRequestInterface $request): ?int
    {
        $session = $request->getAttribute('session');
        if (!$session) {
            return null;
        }

        $impersonatingOrgId = $session->read('impersonating_org_id');
        if (!$impersonatingOrgId) {
            return null;
        }

        // Verify the current user is a super admin
        $identity = $request->getAttribute('identity');
        if (!$identity) {
            // Not authenticated — clear impersonation data
            $session->delete('impersonating_org_id');
            $session->delete('impersonating_org_name');

            return null;
        }

        try {
            $usersTable = $this->fetchTable('Users');
            $user = $usersTable->find()
                ->select(['id', 'is_super_admin'])
                ->where(['id' => $identity->getIdentifier()])
                ->disableHydration()
                ->first();

            if ($user && !empty($user['is_super_admin'])) {
                return (int)$impersonatingOrgId;
            }
        } catch (\Exception $e) {
            // Column may not exist yet — ignore
        }

        // Not a super admin — clear impersonation data
        $session->delete('impersonating_org_id');
        $session->delete('impersonating_org_name');

        return null;
    }

    /**
     * Resolve org from the session's current_organization_id.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveFromSession(ServerRequestInterface $request): ?int
    {
        $session = $request->getAttribute('session');
        if ($session && $session->read('current_organization_id')) {
            return (int)$session->read('current_organization_id');
        }

        return null;
    }

    /**
     * Resolve org from /org/{slug}/... path prefix (dev fallback).
     *
     * @param string $path The request path.
     * @return int|null
     */
    private function resolveFromPathPrefix(string $path): ?int
    {
        if (preg_match('#^/org/([a-z0-9][a-z0-9\-]*[a-z0-9])(/|$)#', $path, $matches)) {
            $slug = $matches[1];
            $orgsTable = $this->fetchTable('Organizations');
            $org = $orgsTable->find()
                ->where(['slug' => $slug, 'active' => true])
                ->first();

            return $org ? $org->id : null;
        }

        return null;
    }

    /**
     * If the authenticated user belongs to exactly one organization, use it.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return int|null
     */
    private function resolveFromUserDefault(ServerRequestInterface $request): ?int
    {
        $identity = $request->getAttribute('identity');
        if (!$identity) {
            return null;
        }

        $userId = $identity->getIdentifier();
        if (!$userId) {
            return null;
        }

        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $memberships = $orgUsersTable->find()
            ->where(['user_id' => $userId])
            ->all();

        if ($memberships->count() === 1) {
            return $memberships->first()->organization_id;
        }

        return null;
    }

    /**
     * Load a full Organization entity by ID.
     *
     * @param int $orgId The organization ID.
     * @return \App\Model\Entity\Organization|null
     */
    private function loadOrganization(int $orgId): mixed
    {
        $orgsTable = $this->fetchTable('Organizations');

        try {
            $org = $orgsTable->find()
                ->where(['id' => $orgId, 'active' => true])
                ->first();

            return $org ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
