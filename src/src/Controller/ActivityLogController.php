<?php
declare(strict_types=1);

namespace App\Controller;

use App\Tenant\TenantContext;

/**
 * ActivityLogController
 *
 * Provides organization-level audit log visibility for org admins.
 * Shows security and activity events for all users in the current organization.
 */
class ActivityLogController extends AppController
{
    /**
     * Index method - Display organization activity log
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_settings');

        // Get all user IDs belonging to the current organization
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgId = TenantContext::getCurrentOrgId();

        $userIds = $orgUsersTable->find()
            ->select(['user_id'])
            ->where(['organization_id' => $orgId])
            ->all()
            ->extract('user_id')
            ->toArray();

        $logsTable = $this->fetchTable('SecurityAuditLogs');

        if (empty($userIds)) {
            // No users in org, return empty result
            $logs = $this->paginate(
                $logsTable->find()->where(['1 = 0']),
                ['limit' => 50]
            );
            $eventType = $this->request->getQuery('event_type');
            $this->set(compact('logs', 'eventType'));

            return;
        }

        $query = $logsTable->find()
            ->contain(['Users'])
            ->where(['SecurityAuditLogs.user_id IN' => $userIds])
            ->orderBy(['SecurityAuditLogs.created' => 'DESC']);

        // Filter by event type
        $eventType = $this->request->getQuery('event_type');
        if ($eventType) {
            $query->where(['SecurityAuditLogs.event_type' => $eventType]);
        }

        $logs = $this->paginate($query, ['limit' => 50]);

        // Get distinct event types for the filter dropdown
        $eventTypes = $logsTable->find()
            ->select(['event_type'])
            ->where(['SecurityAuditLogs.user_id IN' => $userIds])
            ->distinct(['event_type'])
            ->orderBy(['event_type' => 'ASC'])
            ->all()
            ->extract('event_type')
            ->toArray();

        $this->set(compact('logs', 'eventType', 'eventTypes'));
    }
}
