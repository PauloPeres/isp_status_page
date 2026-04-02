<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\MonitorStatsService;
use App\Service\PlanService;
use Cake\I18n\DateTime;

/**
 * MonitorsController — API v2
 *
 * Full CRUD, bulk operations, pause/resume, checks history, and CSV import
 * for the Angular SPA.
 *
 * TASK-NG-004
 */
class MonitorsController extends AppController
{
    protected AuditLogService $auditLogService;

    public function initialize(): void
    {
        parent::initialize();
        $this->auditLogService = new AuditLogService();
    }

    /**
     * GET /api/v2/monitors
     *
     * List monitors with search, tag filter, type filter, status filter, and pagination.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $monitorsTable = $this->fetchTable('Monitors');
        $query = $monitorsTable->find()->orderBy(['Monitors.created' => 'DESC']);

        // Filter by status
        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['Monitors.status' => $status]);
        }

        // Filter by type
        $type = $this->request->getQuery('type');
        if ($type) {
            $query->where(['Monitors.type' => $type]);
        }

        // Filter by active
        $active = $this->request->getQuery('active');
        if ($active !== null && $active !== '') {
            $query->where(['Monitors.active' => (bool)$active]);
        }

        // Filter by tag
        $tag = $this->request->getQuery('tag');
        if ($tag) {
            $escapedTag = str_replace(['%', '_'], ['\\%', '\\_'], $tag);
            $query->where(['Monitors.tags LIKE' => '%"' . $escapedTag . '"%']);
        }

        // Search by name or description
        $search = $this->request->getQuery('search');
        if ($search) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where([
                'OR' => [
                    'Monitors.name LIKE' => '%' . $search . '%',
                    'Monitors.description LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = min((int)$this->request->getQuery('limit', 25), 100);

        $total = $query->count();
        $items = $query->limit($limit)->offset(($page - 1) * $limit)->toArray();

        $this->success([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * GET /api/v2/monitors/{id}
     *
     * Single monitor with last check info, 24h uptime, average response time,
     * 30-day uptime history, and SLA data.
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $statsService = new MonitorStatsService();
        $result = $statsService->getMonitorDetail((int)$id, $this->currentOrgId);

        if ($result === null) {
            $this->error('Monitor not found', 404);

            return;
        }

        $this->success($result);
    }

    /**
     * POST /api/v2/monitors
     *
     * Create a new monitor.
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        // Check plan limit
        $planService = new PlanService();
        $check = $planService->checkLimit($this->currentOrgId, 'monitor');
        if (!$check['allowed']) {
            $this->planLimitError(
                "Monitor limit reached. Your {$check['plan_name']} plan allows {$check['limit']} monitors. Upgrade to add more.",
                $check
            );
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $data = $this->request->getData();
        $data['check_interval'] = $planService->validateCheckInterval($this->currentOrgId, $data['check_interval'] ?? 300);
        $data = $this->processTagsData($data);
        $data = $this->processConfigurationData($data);

        $monitor = $monitorsTable->newEntity($data);

        if ($monitorsTable->save($monitor)) {
            $this->auditLogService->log(
                'monitor_created',
                $this->currentUserId,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['monitor_id' => $monitor->id, 'name' => $monitor->name, 'type' => $monitor->type]
            );

            $this->success(['monitor' => $monitor], 201);
        } else {
            $this->error('Unable to create monitor', 422, $monitor->getErrors());
        }
    }

    /**
     * PUT /api/v2/monitors/{id}
     *
     * Update an existing monitor.
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $data = $this->request->getData();
        $data = $this->processTagsData($data);
        $data = $this->processConfigurationData($data);

        $monitor = $monitorsTable->patchEntity($monitor, $data);

        if ($monitorsTable->save($monitor)) {
            $this->auditLogService->log(
                'monitor_updated',
                $this->currentUserId,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['monitor_id' => $monitor->id, 'name' => $monitor->name, 'type' => $monitor->type]
            );

            $this->success(['monitor' => $monitor]);
        } else {
            $this->error('Unable to update monitor', 422, $monitor->getErrors());
        }
    }

    /**
     * DELETE /api/v2/monitors/{id}
     *
     * Delete a monitor.
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        if ($monitorsTable->delete($monitor)) {
            $this->auditLogService->log(
                'monitor_deleted',
                $this->currentUserId,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['monitor_id' => (int)$id, 'name' => $monitor->name, 'type' => $monitor->type]
            );

            $this->success(['message' => 'Monitor deleted']);
        } else {
            $this->error('Unable to delete monitor', 500);
        }
    }

    /**
     * GET /api/v2/monitors/{id}/checks
     *
     * Recent checks for a monitor with pagination.
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function checks(string $id): void
    {
        $this->request->allowMethod(['get']);

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitorsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $checksTable = $this->fetchTable('MonitorChecks');
        $query = $checksTable->find()
            ->where(['MonitorChecks.monitor_id' => $id])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC']);

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = min((int)$this->request->getQuery('limit', 25), 100);

        $total = $query->count();
        $items = $query->limit($limit)->offset(($page - 1) * $limit)->toArray();

        $this->success([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * POST /api/v2/monitors/{id}/pause
     *
     * Pause a monitor (set active=false).
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function pause(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $monitor->active = false;

        if ($monitorsTable->save($monitor)) {
            $this->success(['monitor' => $monitor]);
        } else {
            $this->error('Unable to pause monitor', 500);
        }
    }

    /**
     * POST /api/v2/monitors/{id}/resume
     *
     * Resume a monitor (set active=true).
     *
     * @param string $id Monitor ID.
     * @return void
     */
    public function resume(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Monitor not found', 404);

            return;
        }

        $monitor->active = true;

        if ($monitorsTable->save($monitor)) {
            $this->success(['monitor' => $monitor]);
        } else {
            $this->error('Unable to resume monitor', 500);
        }
    }

    /**
     * POST /api/v2/monitors/bulk-action
     *
     * Perform bulk pause, resume, or delete on multiple monitors.
     *
     * Expected body: { "action": "pause|resume|delete", "ids": [1, 2, 3] }
     *
     * @return void
     */
    public function bulkAction(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $action = $this->request->getData('action');
        $ids = $this->request->getData('ids', []);

        if (empty($ids) || !is_array($ids)) {
            $this->error('No monitors selected', 400);

            return;
        }

        $ids = array_map('intval', array_filter($ids));

        if (empty($ids)) {
            $this->error('No valid monitor IDs provided', 400);

            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $count = 0;

        switch ($action) {
            case 'pause':
                $count = $monitorsTable->updateAll(
                    ['active' => false],
                    ['id IN' => $ids, 'organization_id' => $this->currentOrgId]
                );
                $this->success(['affected' => $count, 'action' => 'pause']);
                break;

            case 'resume':
                $count = $monitorsTable->updateAll(
                    ['active' => true],
                    ['id IN' => $ids, 'organization_id' => $this->currentOrgId]
                );
                $this->success(['affected' => $count, 'action' => 'resume']);
                break;

            case 'delete':
                $count = $monitorsTable->deleteAll(['id IN' => $ids, 'organization_id' => $this->currentOrgId]);
                $this->success(['affected' => $count, 'action' => 'delete']);
                break;

            default:
                $this->error('Invalid bulk action. Use: pause, resume, or delete', 400);
                break;
        }
    }

    /**
     * POST /api/v2/monitors/import
     *
     * Import monitors from CSV data.
     *
     * Expected body: { "csv": "name,type,url,...\nMy Monitor,http,https://..." }
     * Or multipart with a csv_file upload.
     *
     * @return void
     */
    public function import(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        // Accept CSV content from body or file upload
        $csvContent = $this->request->getData('csv');

        if (empty($csvContent)) {
            $file = $this->request->getUploadedFile('csv_file');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $csvContent = (string)$file->getStream();
            }
        }

        if (empty($csvContent)) {
            $this->error('No CSV data provided. Send "csv" field or upload "csv_file".', 400);

            return;
        }

        $lines = array_filter(explode("\n", $csvContent), 'trim');

        if (count($lines) < 2) {
            $this->error('CSV must have a header row and at least one data row', 400);

            return;
        }

        // Parse header
        $header = str_getcsv(array_shift($lines));
        $header = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        $nameIdx = array_search('name', $header);
        if ($nameIdx === false) {
            $this->error('CSV must contain a "name" column', 400);

            return;
        }

        $typeIdx = array_search('type', $header);
        $urlIdx = array_search('url', $header);
        $hostIdx = array_search('host', $header);
        $portIdx = array_search('port', $header);
        $intervalIdx = array_search('check_interval', $header);
        $tagsIdx = array_search('tags', $header);

        $monitorsTable = $this->fetchTable('Monitors');
        $created = 0;
        $errors = [];
        $lineNum = 1;

        foreach ($lines as $line) {
            $lineNum++;
            $row = str_getcsv(trim($line));
            if (empty(array_filter($row))) {
                continue;
            }

            $name = $row[$nameIdx] ?? '';
            if (empty(trim($name))) {
                $errors[] = "Line {$lineNum}: Name is required.";
                continue;
            }

            $type = ($typeIdx !== false && !empty($row[$typeIdx])) ? strtolower(trim($row[$typeIdx])) : 'http';

            $configuration = [];
            switch ($type) {
                case 'http':
                    $url = ($urlIdx !== false) ? trim($row[$urlIdx] ?? '') : '';
                    if (empty($url)) {
                        $errors[] = "Line {$lineNum}: URL is required for HTTP monitors.";
                        continue 2;
                    }
                    $configuration = ['url' => $url, 'method' => 'GET', 'expected_status_code' => 200];
                    break;

                case 'ping':
                    $host = ($hostIdx !== false) ? trim($row[$hostIdx] ?? '') : '';
                    if (empty($host) && $urlIdx !== false) {
                        $host = trim($row[$urlIdx] ?? '');
                    }
                    if (empty($host)) {
                        $errors[] = "Line {$lineNum}: Host is required for Ping monitors.";
                        continue 2;
                    }
                    $configuration = ['host' => $host];
                    break;

                case 'port':
                    $host = ($hostIdx !== false) ? trim($row[$hostIdx] ?? '') : '';
                    $port = ($portIdx !== false) ? trim($row[$portIdx] ?? '') : '';
                    if (empty($host)) {
                        $errors[] = "Line {$lineNum}: Host is required for Port monitors.";
                        continue 2;
                    }
                    if (empty($port)) {
                        $errors[] = "Line {$lineNum}: Port is required for Port monitors.";
                        continue 2;
                    }
                    $configuration = ['host' => $host, 'port' => (int)$port];
                    break;

                default:
                    $errors[] = "Line {$lineNum}: Invalid type \"{$type}\". Use http, ping, or port.";
                    continue 2;
            }

            $data = [
                'name' => trim($name),
                'type' => $type,
                'configuration' => json_encode($configuration),
                'check_interval' => ($intervalIdx !== false && !empty($row[$intervalIdx])) ? (int)$row[$intervalIdx] : 300,
                'timeout' => 30,
                'retry_count' => 3,
                'status' => 'unknown',
                'active' => true,
            ];

            if ($tagsIdx !== false && !empty($row[$tagsIdx])) {
                $tags = array_values(array_unique(array_filter(array_map('trim', explode(';', $row[$tagsIdx])))));
                $data['tags'] = !empty($tags) ? json_encode($tags) : null;
            }

            $monitor = $monitorsTable->newEntity($data);

            if ($monitorsTable->save($monitor)) {
                $created++;
            } else {
                $validationErrors = [];
                foreach ($monitor->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $validationErrors[] = "{$field}: {$error}";
                    }
                }
                $errors[] = "Line {$lineNum}: " . implode(', ', $validationErrors);
            }
        }

        $this->success([
            'created' => $created,
            'errors' => $errors,
        ], $created > 0 ? 201 : 200);
    }

    /**
     * POST /api/v2/monitors/import-competitor
     *
     * Import monitors from competitor platforms.
     * Supported formats: uptimerobot, pingdom, betteruptime, csv (auto-detected).
     *
     * @return void
     */
    public function importCompetitor(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $content = $this->request->getData('content');
        $format = $this->request->getData('format'); // optional: uptimerobot, pingdom, betteruptime

        if (empty($content)) {
            $file = $this->request->getUploadedFile('file');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $content = (string)$file->getStream();
            }
        }

        if (empty($content)) {
            $this->error('No import data provided. Send "content" field or upload "file".', 400);
            return;
        }

        $importService = new \App\Service\Import\MonitorImportService();
        $result = $importService->parse($content, $format);

        if (empty($result['monitors'])) {
            $this->error('No monitors could be parsed from the import data', 400, $result['errors'] ?? []);
            return;
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $created = 0;
        $errors = $result['errors'];

        foreach ($result['monitors'] as $i => $monitorData) {
            $data = [
                'name' => $monitorData['name'],
                'type' => $monitorData['type'],
                'configuration' => json_encode($monitorData['configuration']),
                'check_interval' => $monitorData['check_interval'] ?? 300,
                'timeout' => 30,
                'retry_count' => 3,
                'status' => 'unknown',
                'active' => $monitorData['active'] ?? true,
            ];

            if (!empty($monitorData['tags'])) {
                $tags = is_array($monitorData['tags'])
                    ? $monitorData['tags']
                    : array_map('trim', explode(',', $monitorData['tags']));
                $data['tags'] = json_encode(array_values(array_filter($tags)));
            }

            $monitor = $monitorsTable->newEntity($data);

            if ($monitorsTable->save($monitor)) {
                $created++;
            } else {
                $validationErrors = [];
                foreach ($monitor->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $validationErrors[] = "{$field}: {$error}";
                    }
                }
                $errors[] = "Monitor \"{$monitorData['name']}\": " . implode(', ', $validationErrors);
            }
        }

        $this->success([
            'created' => $created,
            'total_parsed' => count($result['monitors']),
            'format_detected' => $result['format'],
            'errors' => $errors,
        ], $created > 0 ? 201 : 200);
    }

    /**
     * Process tags from request data.
     *
     * Accepts tags as a comma-separated string or an array and normalises
     * them into a JSON-encoded string for storage.
     *
     * @param array $data Request data.
     * @return array Modified data.
     */
    private function processTagsData(array $data): array
    {
        if (isset($data['tags'])) {
            if (is_string($data['tags'])) {
                $tagsString = $data['tags'];
                if (trim($tagsString) === '') {
                    $data['tags'] = null;
                } else {
                    $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsString)))));
                    $data['tags'] = !empty($tags) ? json_encode($tags) : null;
                }
            } elseif (is_array($data['tags'])) {
                $tags = array_values(array_unique(array_filter(array_map('trim', $data['tags']))));
                $data['tags'] = !empty($tags) ? json_encode($tags) : null;
            }
        }

        return $data;
    }

    /**
     * Filter configuration fields based on monitor type.
     *
     * @param array $data Request data.
     * @return array Modified data.
     */
    private function processConfigurationData(array $data): array
    {
        if (!isset($data['type']) || !isset($data['configuration']) || !is_array($data['configuration'])) {
            return $data;
        }

        $allowedKeys = match ($data['type']) {
            'http' => ['url', 'method', 'expected_status_code', 'headers', 'body',
                       'verify_ssl', 'follow_redirects', 'expected_content'],
            'ping' => ['host', 'packet_count', 'max_packet_loss', 'max_latency'],
            'port' => ['host', 'port', 'protocol', 'send_data', 'expected_response'],
            default => null,
        };

        if ($allowedKeys === null) {
            return $data;
        }

        $filtered = [];
        foreach ($allowedKeys as $key) {
            if (isset($data['configuration'][$key]) && $data['configuration'][$key] !== '') {
                $filtered[$key] = $data['configuration'][$key];
            }
        }
        $data['configuration'] = !empty($filtered) ? json_encode($filtered) : null;

        return $data;
    }
}
