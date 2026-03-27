<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\ApiKey;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Monitors API Controller
 *
 * Provides CRUD + pause/resume/checks endpoints for monitors.
 * Tenant scoping is handled automatically by TenantScopeBehavior.
 */
class MonitorsController extends AppController
{
    /**
     * List all monitors for the current tenant.
     *
     * GET /api/v1/monitors
     *
     * @return void
     */
    public function index(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        $monitors = $this->fetchTable('Monitors')
            ->find()
            ->orderBy(['Monitors.display_order' => 'ASC', 'Monitors.name' => 'ASC'])
            ->all()
            ->toArray();

        $this->success($monitors);
    }

    /**
     * View a single monitor.
     *
     * GET /api/v1/monitors/{id}
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function view(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        try {
            $monitor = $this->fetchTable('Monitors')->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $this->success($monitor);
    }

    /**
     * Create a new monitor.
     *
     * POST /api/v1/monitors
     *
     * @return void
     */
    public function add(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $monitor = $monitorsTable->newEntity($this->request->getData());

        if ($monitorsTable->save($monitor)) {
            $this->success($monitor, 201);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($monitor->getErrors()), 422);
        }
    }

    /**
     * Update an existing monitor.
     *
     * PUT /api/v1/monitors/{id}
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function edit(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $monitor = $monitorsTable->patchEntity($monitor, $this->request->getData());

        if ($monitorsTable->save($monitor)) {
            $this->success($monitor);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($monitor->getErrors()), 422);
        }
    }

    /**
     * Delete a monitor.
     *
     * DELETE /api/v1/monitors/{id}
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function delete(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        if ($monitorsTable->delete($monitor)) {
            $this->success(['id' => (int)$id, 'deleted' => true]);
        } else {
            $this->error('Failed to delete monitor', 500);
        }
    }

    /**
     * List recent checks for a monitor.
     *
     * GET /api/v1/monitors/{id}/checks
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function checks(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitorsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min(max($limit, 1), 200);

        $checks = $this->fetchTable('MonitorChecks')
            ->find()
            ->where(['MonitorChecks.monitor_id' => (int)$id])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC'])
            ->limit($limit)
            ->all()
            ->toArray();

        $this->success($checks);
    }

    /**
     * Pause a monitor (set active = false).
     *
     * POST /api/v1/monitors/{id}/pause
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function pause(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $monitor->active = false;

        if ($monitorsTable->save($monitor)) {
            $this->success($monitor);
        } else {
            $this->error('Failed to pause monitor', 500);
        }
    }

    /**
     * Resume a monitor (set active = true).
     *
     * POST /api/v1/monitors/{id}/resume
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function resume(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $monitor->active = true;

        if ($monitorsTable->save($monitor)) {
            $this->success($monitor);
        } else {
            $this->error('Failed to resume monitor', 500);
        }
    }

    /**
     * Format entity validation errors into a readable string.
     *
     * @param array $errors The validation errors array.
     * @return string
     */
    private function formatErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $rule => $message) {
                $messages[] = "{$field}: {$message}";
            }
        }

        return implode('; ', $messages);
    }
}
