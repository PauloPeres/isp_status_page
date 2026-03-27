<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\ApiKey;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Checks API Controller
 *
 * Provides read-only endpoints for monitor checks.
 * Tenant scoping is handled automatically by TenantScopeBehavior.
 */
class ChecksController extends AppController
{
    /**
     * List checks, optionally filtered by monitor_id.
     *
     * GET /api/v1/checks
     * GET /api/v1/checks?monitor_id=1
     *
     * @return void
     */
    public function index(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        $query = $this->fetchTable('MonitorChecks')
            ->find()
            ->contain(['Monitors'])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC']);

        $monitorId = $this->request->getQuery('monitor_id');
        if ($monitorId) {
            $query->where(['MonitorChecks.monitor_id' => (int)$monitorId]);
        }

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min(max($limit, 1), 200);
        $query->limit($limit);

        $checks = $query->all()->toArray();

        $this->success($checks);
    }

    /**
     * View a single check.
     *
     * GET /api/v1/checks/{id}
     *
     * @param string $id Check ID.
     * @return void
     */
    public function view(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        try {
            $check = $this->fetchTable('MonitorChecks')->get((int)$id, contain: ['Monitors']);
        } catch (RecordNotFoundException $e) {
            $this->error('Check not found', 404);

            return;
        }

        $this->success($check);
    }
}
