<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\PlanService;

/**
 * IntegrationsController (TASK-NG-006)
 *
 * Manages third-party integrations (IXC, Zabbix, REST API, etc.).
 */
class IntegrationsController extends AppController
{
    protected AuditLogService $auditLogService;

    public function initialize(): void
    {
        parent::initialize();
        $this->auditLogService = new AuditLogService();
    }

    /**
     * GET /api/v2/integrations
     *
     * List all integrations for the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('Integrations');
        $integrations = $table->find()
            ->where(['Integrations.organization_id' => $this->currentOrgId])
            ->orderBy(['Integrations.name' => 'ASC'])
            ->all();

        $this->success(['integrations' => $integrations->toArray()]);
    }

    /**
     * GET /api/v2/integrations/{id}
     *
     * View a single integration.
     *
     * @param string $id Integration ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('Integrations');
        $integration = $table->find()
            ->where([
                'Integrations.id' => $id,
                'Integrations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$integration) {
            $this->error('Integration not found', 404);

            return;
        }

        $this->success(['integration' => $integration]);
    }

    /**
     * POST /api/v2/integrations
     *
     * Create a new integration.
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $planService = new PlanService();
        $check = $planService->checkFeature($this->currentOrgId, 'api_access');
        if (!$check['allowed']) {
            $this->planLimitError("Integrations are not available on your {$check['plan_name']} plan. Upgrade to use integrations.", $check);
            return;
        }

        $table = $this->fetchTable('Integrations');
        $integration = $table->newEntity($this->request->getData());
        $integration->set('organization_id', $this->currentOrgId);

        if (!$table->save($integration)) {
            $this->error('Validation failed', 422, $integration->getErrors());

            return;
        }

        $this->auditLogService->log(
            'integration_created',
            $this->currentUserId,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            ['integration_id' => $integration->id, 'name' => $integration->name, 'type' => $integration->type ?? null]
        );

        $this->success(['integration' => $integration], 201);
    }

    /**
     * PUT /api/v2/integrations/{id}
     *
     * Update an existing integration.
     *
     * @param string $id Integration ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Integrations');
        $integration = $table->find()
            ->where([
                'Integrations.id' => $id,
                'Integrations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$integration) {
            $this->error('Integration not found', 404);

            return;
        }

        $integration = $table->patchEntity($integration, $this->request->getData());
        if (!$table->save($integration)) {
            $this->error('Validation failed', 422, $integration->getErrors());

            return;
        }

        $this->success(['integration' => $integration]);
    }

    /**
     * DELETE /api/v2/integrations/{id}
     *
     * Delete an integration.
     *
     * @param string $id Integration ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Integrations');
        $integration = $table->find()
            ->where([
                'Integrations.id' => $id,
                'Integrations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$integration) {
            $this->error('Integration not found', 404);

            return;
        }

        if (!$table->delete($integration)) {
            $this->error('Failed to delete integration', 500);

            return;
        }

        $this->auditLogService->log(
            'integration_deleted',
            $this->currentUserId,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            ['integration_id' => (int)$id, 'name' => $integration->name, 'type' => $integration->type ?? null]
        );

        $this->success(['message' => 'Integration deleted']);
    }

    /**
     * POST /api/v2/integrations/{id}/test
     *
     * Test the connection for an integration.
     *
     * @param string $id Integration ID.
     * @return void
     */
    public function test(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Integrations');
        $integration = $table->find()
            ->where([
                'Integrations.id' => $id,
                'Integrations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$integration) {
            $this->error('Integration not found', 404);

            return;
        }

        try {
            $service = new \App\Service\IntegrationService();
            $result = $service->testConnection($integration);

            $this->success(['result' => $result]);
        } catch (\Exception $e) {
            $this->error('Connection test failed: ' . $e->getMessage(), 422);
        }
    }
}
