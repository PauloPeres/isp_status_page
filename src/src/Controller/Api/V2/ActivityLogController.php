<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * ActivityLogController (TASK-NG-013)
 *
 * View organization activity log with optional event type filtering.
 */
class ActivityLogController extends AppController
{
    /**
     * GET /api/v2/activity-log
     *
     * List activity log entries with optional event_type filter.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SecurityAuditLogs');
        $query = $table->find()
            ->orderBy(['SecurityAuditLogs.created' => 'DESC']);

        $eventType = $this->request->getQuery('event_type');
        if (!empty($eventType)) {
            $query->where(['SecurityAuditLogs.event_type' => $eventType]);
        }

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min($limit, 200);
        $page = (int)($this->request->getQuery('page') ?: 1);

        $entries = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        $this->success([
            'activity_log' => $entries->toArray(),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
            ],
        ]);
    }
}
