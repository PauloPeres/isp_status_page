<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Base API controller for /api/v1/* endpoints.
 *
 * Does NOT extend the main App\Controller\AppController to avoid
 * loading session auth, CSRF, Flash, i18n, and tenant resolution
 * that the middleware already handles for API routes.
 */
class AppController extends Controller
{
    /**
     * Initialize — set JSON view for all API responses.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * Before filter — bypass CakePHP Authentication component if loaded.
     *
     * API auth is handled by ApiAuthMiddleware, so we mark every action
     * as unauthenticated from the component's perspective.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if ($this->components()->has('Authentication')) {
            $this->Authentication->addUnauthenticatedActions($this->getActions());
        }
    }

    /**
     * Return a JSON success response.
     *
     * @param mixed $data The data to serialize.
     * @param int $status HTTP status code.
     * @return void
     */
    protected function success(mixed $data, int $status = 200): void
    {
        $this->response = $this->response->withStatus($status);
        $this->set([
            'success' => true,
            'data' => $data,
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * Return a JSON error response.
     *
     * @param string $message The error message.
     * @param int $status HTTP status code.
     * @return void
     */
    protected function error(string $message, int $status = 400): void
    {
        $this->response = $this->response->withStatus($status);
        $this->set([
            'error' => true,
            'message' => $message,
        ]);
        $this->viewBuilder()->setOption('serialize', ['error', 'message']);
    }

    /**
     * Check if the current API key has the required permission.
     *
     * Returns true when access is granted. When access is denied the
     * method sets a 403 error response and returns false — the caller
     * should return early.
     *
     * @param string $permission The required permission (read, write, admin).
     * @return bool
     */
    protected function requirePermission(string $permission): bool
    {
        $apiKey = $this->request->getAttribute('apiKey');

        if (!$apiKey) {
            $this->error('Authentication required', 401);

            return false;
        }

        if (!$apiKey->hasPermission($permission)) {
            $this->error('Insufficient permissions — requires ' . $permission, 403);

            return false;
        }

        return true;
    }

    /**
     * Get all public action names defined in this controller.
     *
     * Used to register every action as "unauthenticated" for the
     * Authentication component (API auth is handled by middleware).
     *
     * @return array<string>
     */
    private function getActions(): array
    {
        $reflection = new \ReflectionClass($this);
        $actions = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->class === $reflection->getName()
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
