<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\PlanService;

/**
 * ApiKeysController (TASK-NG-010)
 *
 * Manage API keys for the current organization.
 */
class ApiKeysController extends AppController
{
    protected AuditLogService $auditLogService;

    public function initialize(): void
    {
        parent::initialize();
        $this->auditLogService = new AuditLogService();
    }

    /**
     * GET /api/v2/api-keys
     *
     * List all API keys for the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ApiKeys');
        $keys = $table->find()
            ->where(['ApiKeys.organization_id' => $this->currentOrgId])
            ->orderBy(['ApiKeys.created' => 'DESC'])
            ->all();

        $this->success(['api_keys' => $keys->toArray()]);
    }

    /**
     * POST /api/v2/api-keys
     *
     * Create a new API key.
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
            $this->planLimitError("API keys are not available on your {$check['plan_name']} plan. Upgrade to use the API.", $check);
            return;
        }

        $table = $this->fetchTable('ApiKeys');
        $key = $table->newEntity($this->request->getData());
        $key->set('organization_id', $this->currentOrgId);
        $key->set('user_id', $this->currentUserId);
        $key->set('key', bin2hex(random_bytes(32)));

        if (!$table->save($key)) {
            $this->error('Validation failed', 422, $key->getErrors());

            return;
        }

        $this->auditLogService->log(
            'api_key_created',
            $this->currentUserId,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            ['api_key_id' => $key->id, 'name' => $key->name ?? null],
            $this->currentOrgId ?: null
        );

        $this->success(['api_key' => $key], 201);
    }

    /**
     * DELETE /api/v2/api-keys/{id}
     *
     * Delete an API key.
     *
     * @param string $id API key ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ApiKeys');
        $key = $this->resolveOrgEntity('ApiKeys', $id);

        if (!$key) {
            $this->error('API key not found', 404);

            return;
        }

        if (!$table->delete($key)) {
            $this->error('Failed to delete API key', 500);

            return;
        }

        $this->auditLogService->log(
            'api_key_deleted',
            $this->currentUserId,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            ['api_key_id' => (int)$id, 'name' => $key->name ?? null],
            $this->currentOrgId ?: null
        );

        $this->success(['message' => 'API key deleted']);
    }
}
