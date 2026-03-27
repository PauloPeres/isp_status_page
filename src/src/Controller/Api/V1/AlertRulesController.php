<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\ApiKey;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * AlertRules API Controller
 *
 * Provides full CRUD for alert rules.
 * Tenant scoping is handled automatically by TenantScopeBehavior.
 */
class AlertRulesController extends AppController
{
    /**
     * List all alert rules for the current tenant.
     *
     * GET /api/v1/alert-rules
     *
     * @return void
     */
    public function index(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        $query = $this->fetchTable('AlertRules')
            ->find()
            ->contain(['Monitors'])
            ->orderBy(['AlertRules.created' => 'DESC']);

        $monitorId = $this->request->getQuery('monitor_id');
        if ($monitorId) {
            $query->where(['AlertRules.monitor_id' => (int)$monitorId]);
        }

        $active = $this->request->getQuery('active');
        if ($active !== null) {
            $query->where(['AlertRules.active' => (bool)$active]);
        }

        $alertRules = $query->all()->toArray();

        $this->success($alertRules);
    }

    /**
     * View a single alert rule.
     *
     * GET /api/v1/alert-rules/{id}
     *
     * @param string $id AlertRule ID.
     * @return void
     */
    public function view(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        try {
            $alertRule = $this->fetchTable('AlertRules')->get((int)$id, contain: ['Monitors']);
        } catch (RecordNotFoundException $e) {
            $this->error('Alert rule not found', 404);

            return;
        }

        $this->success($alertRule);
    }

    /**
     * Create a new alert rule.
     *
     * POST /api/v1/alert-rules
     *
     * @return void
     */
    public function add(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $alertRulesTable = $this->fetchTable('AlertRules');
        $alertRule = $alertRulesTable->newEntity($this->request->getData());

        if ($alertRulesTable->save($alertRule)) {
            $this->success($alertRule, 201);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($alertRule->getErrors()), 422);
        }
    }

    /**
     * Update an existing alert rule.
     *
     * PUT /api/v1/alert-rules/{id}
     *
     * @param string $id AlertRule ID.
     * @return void
     */
    public function edit(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $alertRulesTable = $this->fetchTable('AlertRules');

        try {
            $alertRule = $alertRulesTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Alert rule not found', 404);

            return;
        }

        $alertRule = $alertRulesTable->patchEntity($alertRule, $this->request->getData());

        if ($alertRulesTable->save($alertRule)) {
            $this->success($alertRule);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($alertRule->getErrors()), 422);
        }
    }

    /**
     * Delete an alert rule.
     *
     * DELETE /api/v1/alert-rules/{id}
     *
     * @param string $id AlertRule ID.
     * @return void
     */
    public function delete(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $alertRulesTable = $this->fetchTable('AlertRules');

        try {
            $alertRule = $alertRulesTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Alert rule not found', 404);

            return;
        }

        if ($alertRulesTable->delete($alertRule)) {
            $this->success(['id' => (int)$id, 'deleted' => true]);
        } else {
            $this->error('Failed to delete alert rule', 500);
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
