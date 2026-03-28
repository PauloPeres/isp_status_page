<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

/**
 * SecurityLogs Controller (Super Admin) — TASK-AUTH-018
 *
 * Provides a paginated, filterable view of the security_audit_logs table.
 */
class SecurityLogsController extends AppController
{
    /**
     * Index — paginated audit log listing, filterable by event_type.
     *
     * @return void
     */
    public function index(): void
    {
        $table = $this->fetchTable('SecurityAuditLogs');
        $query = $table->find()
            ->contain(['Users'])
            ->orderBy(['SecurityAuditLogs.created' => 'DESC']);

        // Filter by event type
        $eventType = $this->request->getQuery('event_type');
        if ($eventType) {
            $query->where(['SecurityAuditLogs.event_type' => $eventType]);
        }

        // Filter by IP
        $ip = $this->request->getQuery('ip');
        if ($ip) {
            $query->where(['SecurityAuditLogs.ip_address' => $ip]);
        }

        // Filter by user
        $userId = $this->request->getQuery('user_id');
        if ($userId) {
            $query->where(['SecurityAuditLogs.user_id' => (int)$userId]);
        }

        $logs = $this->paginate($query, ['limit' => 50]);

        // Get distinct event types for the filter dropdown
        $eventTypes = $table->find()
            ->select(['event_type'])
            ->distinct(['event_type'])
            ->orderBy(['event_type' => 'ASC'])
            ->all()
            ->extract('event_type')
            ->toArray();

        $this->set(compact('logs', 'eventTypes', 'eventType'));
    }
}
