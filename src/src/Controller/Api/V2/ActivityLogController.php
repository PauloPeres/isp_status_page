<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * ActivityLogController (TASK-NG-013)
 *
 * View organization activity log with optional event type filtering.
 * Tenant-scoped: only returns logs belonging to the current organization.
 */
class ActivityLogController extends AppController
{
    /**
     * GET /api/v2/activity-log
     *
     * List activity log entries with optional event_type filter.
     * Scoped to the current organization. Super admins also see
     * system-level events (organization_id IS NULL).
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

        // Tenant isolation: filter by organization_id
        if ($this->isSuperAdmin) {
            // Super admins see their org's logs + system-level events (null org)
            $query->where([
                'OR' => [
                    'SecurityAuditLogs.organization_id' => $this->currentOrgId,
                    'SecurityAuditLogs.organization_id IS' => null,
                ],
            ]);
        } else {
            $query->where(['SecurityAuditLogs.organization_id' => $this->currentOrgId]);
        }

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

    /**
     * GET /api/v2/activity-log/export
     *
     * Export audit logs as CSV or JSON for compliance.
     * Query params: format (csv|json, default csv), from, to, event_type
     * Scoped to the current organization.
     *
     * @return void
     */
    public function export(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $format = strtolower($this->request->getQuery('format') ?: 'csv');
        $from = $this->request->getQuery('from');
        $to = $this->request->getQuery('to');
        $eventType = $this->request->getQuery('event_type');

        $table = $this->fetchTable('SecurityAuditLogs');
        $query = $table->find()
            ->contain(['Users' => ['fields' => ['id', 'username', 'email']]])
            ->orderBy(['SecurityAuditLogs.created' => 'ASC']);

        // Tenant isolation: filter by organization_id
        if ($this->isSuperAdmin) {
            $query->where([
                'OR' => [
                    'SecurityAuditLogs.organization_id' => $this->currentOrgId,
                    'SecurityAuditLogs.organization_id IS' => null,
                ],
            ]);
        } else {
            $query->where(['SecurityAuditLogs.organization_id' => $this->currentOrgId]);
        }

        if (!empty($eventType)) {
            $query->where(['SecurityAuditLogs.event_type' => $eventType]);
        }
        if (!empty($from)) {
            $query->where(['SecurityAuditLogs.created >=' => $from]);
        }
        if (!empty($to)) {
            $query->where(['SecurityAuditLogs.created <=' => $to . ' 23:59:59']);
        }

        $entries = $query->limit(10000)->all()->toArray();

        if ($format === 'json') {
            $rows = [];
            foreach ($entries as $entry) {
                $rows[] = [
                    'id' => $entry->id,
                    'event_type' => $entry->event_type,
                    'user_id' => $entry->user_id,
                    'username' => $entry->user->username ?? null,
                    'email' => $entry->user->email ?? null,
                    'ip_address' => $entry->ip_address,
                    'user_agent' => $entry->user_agent,
                    'details' => $entry->details ? json_decode($entry->details, true) : null,
                    'created' => $entry->created ? $entry->created->format('Y-m-d H:i:s') : null,
                ];
            }

            $this->response = $this->response
                ->withType('application/json')
                ->withHeader('Content-Disposition', 'attachment; filename="audit_log_export.json"');
            $this->set('data', $rows);
            $this->viewBuilder()->setOption('serialize', 'data');
            return;
        }

        // CSV format
        $csvLines = [];
        $csvLines[] = implode(',', ['ID', 'Event Type', 'User ID', 'Username', 'Email', 'IP Address', 'Details', 'Timestamp']);

        foreach ($entries as $entry) {
            $csvLines[] = implode(',', [
                $entry->id,
                $this->csvEscape($entry->event_type),
                $entry->user_id ?? '',
                $this->csvEscape($entry->user->username ?? ''),
                $this->csvEscape($entry->user->email ?? ''),
                $this->csvEscape($entry->ip_address ?? ''),
                $this->csvEscape($entry->details ?? ''),
                $entry->created ? $entry->created->format('Y-m-d H:i:s') : '',
            ]);
        }

        $csvContent = implode("\n", $csvLines);

        $this->response = $this->response
            ->withType('text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="audit_log_export.csv"')
            ->withStringBody($csvContent);
        $this->autoRender = false;
    }

    /**
     * Escape a value for CSV output.
     */
    private function csvEscape(string $value): string
    {
        // Prevent formula injection in spreadsheets
        if (preg_match('/^[=+\-@\t\r]/', $value)) {
            $value = "'" . $value;
        }

        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
