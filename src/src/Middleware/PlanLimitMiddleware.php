<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\PlanService;
use App\Tenant\TenantContext;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Plan Limit Middleware
 *
 * Checks plan limits before allowing resource creation actions.
 * Currently enforces monitor limits on POST to /monitors/add.
 */
class PlanLimitMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and check plan limits for create actions.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only check POST requests (create actions)
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        // Only check if tenant context is set
        if (!TenantContext::isSet()) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        $orgId = TenantContext::getCurrentOrgId();

        // Check monitor limit on POST to /monitors/add
        if ($this->isMonitorCreateAction($path) && $orgId !== null) {
            $planService = new PlanService();

            if (!$planService->canAddMonitor($orgId)) {
                $session = $request->getAttribute('session');
                if ($session) {
                    $flash = $session->read('Flash') ?? [];
                    $flash['flash'][] = [
                        'message' => __("You've reached the monitor limit for your plan. Upgrade to add more monitors."),
                        'key' => 'flash',
                        'element' => 'flash/error',
                        'params' => [],
                    ];
                    $session->write('Flash', $flash);
                }

                $response = new Response();

                return $response
                    ->withHeader('Location', '/billing/plans')
                    ->withStatus(302);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Check if the request path is a monitor create action.
     *
     * @param string $path The request path.
     * @return bool
     */
    private function isMonitorCreateAction(string $path): bool
    {
        return preg_match('#^/monitors/add$#i', rtrim($path, '/')) === 1;
    }
}
