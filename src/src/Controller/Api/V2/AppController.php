<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Base API controller for /api/v2/* endpoints.
 *
 * Does NOT extend the main App\Controller\AppController to avoid
 * loading session auth, CSRF, Flash, i18n, and tenant resolution
 * that the middleware already handles for web routes.
 *
 * JWT authentication is handled by JwtAuthMiddleware. The decoded
 * payload is available via $this->jwtPayload and the convenience
 * properties $currentUserId, $currentOrgId, $currentRole, $isSuperAdmin.
 */
class AppController extends Controller
{
    /**
     * Decoded JWT payload (set by JwtAuthMiddleware).
     *
     * @var object|null
     */
    protected ?object $jwtPayload = null;

    /**
     * Current authenticated user ID.
     *
     * @var int
     */
    protected int $currentUserId = 0;

    /**
     * Current organization ID.
     *
     * @var int
     */
    protected int $currentOrgId = 0;

    /**
     * Current user role within the organization.
     *
     * @var string
     */
    protected string $currentRole = '';

    /**
     * Whether the current user is a super admin.
     *
     * @var bool
     */
    protected bool $isSuperAdmin = false;

    /**
     * Initialize — set JSON view for all API v2 responses.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * Before filter — extract JWT payload and bypass CakePHP Authentication.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // Extract JWT payload from request (set by JwtAuthMiddleware)
        $this->jwtPayload = $this->request->getAttribute('jwt_payload');
        if ($this->jwtPayload) {
            $this->currentUserId = (int)($this->jwtPayload->sub ?? 0);
            $this->currentOrgId = (int)($this->jwtPayload->org_id ?? 0);
            $this->currentRole = (string)($this->jwtPayload->role ?? '');
            $this->isSuperAdmin = (bool)($this->jwtPayload->is_super_admin ?? false);
        }

        // Bypass CakePHP Authentication component — JWT middleware handles auth
        if ($this->components()->has('Authentication')) {
            $this->Authentication->addUnauthenticatedActions($this->getAllActions());
        }
    }

    /**
     * Return a JSON success response.
     *
     * @param mixed $data The data to include in the response (optional).
     * @param int $status HTTP status code.
     * @return void
     */
    protected function success(mixed $data = null, int $status = 200): void
    {
        $body = ['success' => true];
        if ($data !== null) {
            $body['data'] = $data;
        }

        $this->response = $this->response->withStatus($status);
        $this->set('response', $body);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Return a JSON error response.
     *
     * @param string $message The error message.
     * @param int $status HTTP status code.
     * @param mixed $errors Additional error details (optional).
     * @return void
     */
    protected function error(string $message, int $status = 400, mixed $errors = null): void
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        $this->response = $this->response->withStatus($status);
        $this->set('response', $body);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Return a 402 plan limit error with structured upgrade info.
     *
     * @param string $message Human-readable message
     * @param array $limitData Data from PlanService::checkLimit() or checkFeature()
     * @return void
     */
    protected function planLimitError(string $message, array $limitData = []): void
    {
        $body = [
            'success' => false,
            'message' => $message,
            'error_type' => 'plan_limit_exceeded',
            'data' => $limitData,
        ];

        $this->response = $this->response->withStatus(402);
        $this->set('response', $body);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Check if the current user has one of the required roles.
     *
     * Sets a 403 error response and returns false when the check fails.
     *
     * @param array<string> $roles Allowed roles.
     * @return bool True if the user has a matching role.
     */
    protected function requireRole(array $roles): bool
    {
        if ($this->isSuperAdmin) {
            return true;
        }

        if (!in_array($this->currentRole, $roles, true)) {
            $this->error('Insufficient permissions', 403);

            return false;
        }

        return true;
    }

    /**
     * Get all public action names defined in the concrete controller.
     *
     * Used to register every action as "unauthenticated" from the
     * CakePHP Authentication component perspective.
     *
     * @return array<string>
     */
    private function getAllActions(): array
    {
        $reflection = new \ReflectionClass($this);
        $actions = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->getDeclaringClass()->getName() === static::class
                && !str_starts_with($method->getName(), '_')
                && $method->getName() !== 'initialize'
                && $method->getName() !== 'beforeFilter'
            ) {
                $actions[] = $method->getName();
            }
        }

        return $actions;
    }
}
