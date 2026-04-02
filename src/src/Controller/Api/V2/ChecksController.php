<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * ChecksController (API v2)
 *
 * Read-only access to monitor check results for the current organization.
 */
class ChecksController extends AppController
{
    /**
     * GET /api/v2/checks
     *
     * List recent checks across all monitors in the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $monitorsTable = $this->fetchTable('Monitors');
        $monitorIds = $monitorsTable->find()
            ->where(['Monitors.organization_id' => $this->currentOrgId])
            ->select(['Monitors.id'])
            ->all()
            ->extract('id')
            ->toArray();

        if (empty($monitorIds)) {
            $this->success(['checks' => [], 'pagination' => ['page' => 1, 'limit' => 50, 'total' => 0, 'pages' => 0]]);

            return;
        }

        $checksTable = $this->fetchTable('MonitorChecks');
        $limit = min((int)($this->request->getQuery('limit') ?: 50), 200);
        $page = max((int)($this->request->getQuery('page') ?: 1), 1);

        $query = $checksTable->find()
            ->contain(['Monitors' => ['fields' => ['id', 'name']]])
            ->where(['MonitorChecks.monitor_id IN' => $monitorIds])
            ->orderBy(['MonitorChecks.created' => 'DESC', 'MonitorChecks.id' => 'DESC']);

        $monitorId = $this->request->getQuery('monitor_id');
        if (!empty($monitorId) && in_array((int)$monitorId, $monitorIds)) {
            $query->where(['MonitorChecks.monitor_id' => (int)$monitorId]);
        }

        // Filter by status (success, failure, degraded, unknown, up, down)
        $status = $this->request->getQuery('status');
        if (!empty($status) && in_array($status, ['success', 'failure', 'degraded', 'unknown', 'up', 'down'])) {
            $query->where(['MonitorChecks.status' => $status]);
        }

        // Filter by date range
        $from = $this->request->getQuery('from');
        $to = $this->request->getQuery('to');
        if (!empty($from)) {
            try {
                $fromDate = new \DateTime($from);
                $query->where(['MonitorChecks.created >=' => $fromDate->format('Y-m-d H:i:s')]);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }
        if (!empty($to)) {
            try {
                $toDate = new \DateTime($to);
                $query->where(['MonitorChecks.created <=' => $toDate->format('Y-m-d H:i:s')]);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $total = (clone $query)->count();

        $checks = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        $this->success([
            'checks' => $checks->toArray(),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * GET /api/v2/checks/{id}
     *
     * View a single check result.
     *
     * @param string $id Check ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $checksTable = $this->fetchTable('MonitorChecks');
        $check = $checksTable->find()
            ->contain(['Monitors'])
            ->where(['MonitorChecks.id' => $id])
            ->first();

        if (!$check || $check->monitor->organization_id !== $this->currentOrgId) {
            $this->error('Check not found', 404);

            return;
        }

        $this->success(['check' => $check]);
    }
}
