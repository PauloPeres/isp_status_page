<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

/**
 * Super Admin SecurityLogsController (TASK-NG-014)
 *
 * View platform-wide security audit logs.
 */
class SecurityLogsController extends AppController
{
    /**
     * GET /api/v2/super-admin/security-logs
     *
     * List security audit log entries with pagination.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('ActivityLogs');
        $query = $table->find()
            ->where(['ActivityLogs.event_type IN' => [
                'login',
                'login_failed',
                'logout',
                'password_change',
                'two_factor_enabled',
                'two_factor_disabled',
                'api_key_created',
                'api_key_deleted',
                'impersonation_start',
                'impersonation_stop',
            ]])
            ->orderBy(['ActivityLogs.created' => 'DESC']);

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min($limit, 200);
        $page = (int)($this->request->getQuery('page') ?: 1);

        $eventType = $this->request->getQuery('event_type');
        if (!empty($eventType)) {
            $query->where(['ActivityLogs.event_type' => $eventType]);
        }

        $entries = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        $this->success([
            'security_logs' => $entries->toArray(),
            'pagination' => ['page' => $page, 'limit' => $limit],
        ]);
    }
}
