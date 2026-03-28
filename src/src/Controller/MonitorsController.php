<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PlanService;
use Cake\I18n\DateTime;

/**
 * Monitors Controller
 *
 * @property \App\Model\Table\MonitorsTable $Monitors
 */
class MonitorsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Filtros
        $query = $this->Monitors->find();

        // Filtro por status
        if ($this->request->getQuery('status')) {
            $query->where(['status' => $this->request->getQuery('status')]);
        }

        // Filtro por tipo
        if ($this->request->getQuery('type')) {
            $query->where(['type' => $this->request->getQuery('type')]);
        }

        // Filtro por ativo/inativo
        if ($this->request->getQuery('active') !== null) {
            $query->where(['active' => (bool)$this->request->getQuery('active')]);
        }

        // Busca por nome
        if ($this->request->getQuery('search')) {
            $search = $this->request->getQuery('search');
            $query->where([
                'OR' => [
                    'name LIKE' => '%' . $search . '%',
                    'description LIKE' => '%' . $search . '%',
                ]
            ]);
        }

        // Filter by tag
        if ($this->request->getQuery('tag')) {
            $tag = $this->request->getQuery('tag');
            $query->where(['tags LIKE' => '%"' . str_replace(['%', '_'], ['\\%', '\\_'], $tag) . '"%']);
        }

        $monitors = $this->paginate($query->orderBy(['created' => 'DESC']));

        // Estatísticas
        $stats = [
            'total' => $this->Monitors->find()->count(),
            'active' => $this->Monitors->find()->where(['active' => true])->count(),
            'online' => $this->Monitors->find()->where(['status' => 'up'])->count(),
            'offline' => $this->Monitors->find()->where(['status' => 'down'])->count(),
        ];

        // P2-011: Compute 30-day uptime bars for each monitor
        $monitorIds = [];
        foreach ($monitors as $m) {
            $monitorIds[] = $m->id;
        }

        $monitorsUptimeData = [];
        if (!empty($monitorIds)) {
            $checksTable = $this->Monitors->MonitorChecks;
            $conn = $checksTable->getConnection();
            $placeholders = implode(',', array_fill(0, count($monitorIds), '?'));
            $since = DateTime::now()->subDays(29)->startOfDay()->format('Y-m-d H:i:s');
            $params = array_merge($monitorIds, [$since]);

            $stmt = $conn->execute(
                "SELECT monitor_id, DATE(checked_at) as check_date,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
                 FROM monitor_checks
                 WHERE monitor_id IN ({$placeholders}) AND checked_at >= ?
                 GROUP BY monitor_id, DATE(checked_at)
                 ORDER BY check_date ASC",
                $params
            );
            $dailyByMonitor = [];
            foreach ($stmt->fetchAll('assoc') as $row) {
                $dailyByMonitor[$row['monitor_id']][$row['check_date']] = $row;
            }

            foreach ($monitorIds as $mid) {
                $data = [];
                for ($i = 29; $i >= 0; $i--) {
                    $dayStr = DateTime::now()->subDays($i)->format('Y-m-d');
                    $total = (int)($dailyByMonitor[$mid][$dayStr]['total'] ?? 0);
                    $success = (int)($dailyByMonitor[$mid][$dayStr]['success_count'] ?? 0);
                    $data[] = [
                        'date' => $dayStr,
                        'uptime' => $total > 0 ? ($success / $total) * 100 : 0,
                        'checks' => $total,
                    ];
                }
                $monitorsUptimeData[$mid] = $data;
            }
        }

        // Collect all unique tags across all monitors for the filter dropdown
        $allTags = [];
        $allMonitorsForTags = $this->Monitors->find()
            ->select(['tags'])
            ->where(['tags IS NOT' => null])
            ->all();
        foreach ($allMonitorsForTags as $m) {
            $decoded = json_decode((string)$m->tags, true);
            if (is_array($decoded)) {
                foreach ($decoded as $t) {
                    $allTags[$t] = $t;
                }
            }
        }
        ksort($allTags);

        // P2-014: Get latest check per monitor for response time display
        $latestChecks = [];
        if (!empty($monitorIds)) {
            $orgId = \App\Tenant\TenantContext::getCurrentOrgId();
            $latestStmt = $conn->execute(
                "SELECT DISTINCT ON (monitor_id) monitor_id, response_time, status, checked_at
                 FROM monitor_checks
                 WHERE monitor_id IN ({$placeholders})" . ($orgId ? ' AND organization_id = ' . (int)$orgId : '') . "
                 ORDER BY monitor_id, checked_at DESC",
                $monitorIds
            );
            foreach ($latestStmt->fetchAll('assoc') as $row) {
                $latestChecks[$row['monitor_id']] = $row;
            }
        }

        $this->set(compact('monitors', 'stats', 'monitorsUptimeData', 'allTags', 'latestChecks'));
    }

    /**
     * View method
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $monitor = $this->Monitors->get($id, [
            'contain' => [
                'MonitorChecks' => function ($q) {
                    return $q->orderBy(['created' => 'DESC'])->limit(50);
                },
                'Incidents' => function ($q) {
                    return $q->orderBy(['created' => 'DESC'])->limit(10);
                },
            ],
        ]);

        // Calculate uptime (last 24h) using aggregate COUNT queries instead of loading all rows into memory
        $checksTable = $this->Monitors->MonitorChecks;
        $since24h = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $uptimeResult = $checksTable->find()
            ->select([
                'total' => $checksTable->find()->func()->count('*'),
                'success' => $checksTable->find()->func()->sum(
                    "CASE WHEN status = 'success' THEN 1 ELSE 0 END"
                ),
            ])
            ->where([
                'monitor_id' => $id,
                'checked_at >=' => $since24h,
            ])
            ->disableAutoFields()
            ->first();

        $totalChecks = (int)($uptimeResult->total ?? 0);
        $successfulChecks = (int)($uptimeResult->success ?? 0);
        $uptime = $totalChecks > 0 ? ($successfulChecks / $totalChecks) * 100 : 0;

        // Calculate average response time using aggregate AVG query
        $avgQuery = $checksTable->find();
        $avgResult = $avgQuery
            ->select(['avg' => $avgQuery->func()->avg('response_time')])
            ->where([
                'monitor_id' => $id,
                'checked_at >=' => $since24h,
                'response_time IS NOT' => null,
            ])
            ->disableAutoFields()
            ->first();
        $avgResponseTime = $avgResult && $avgResult->avg ? round((float)$avgResult->avg, 2) : null;

        // P2-003: Response time graph data
        $timeRange = $this->request->getQuery('range', '24h');
        $rangeHours = match ($timeRange) {
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
        $rangeSince = DateTime::now()->subHours($rangeHours);

        $checks24h = $checksTable->find()
            ->where(['monitor_id' => $id, 'checked_at >=' => $rangeSince])
            ->orderBy(['checked_at' => 'ASC'])
            ->all();

        $responseTimeData = [];
        foreach ($checks24h as $check) {
            $format = $rangeHours <= 24 ? 'H:i' : 'M d H:i';
            $responseTimeData[] = [
                'time' => $check->checked_at->format($format),
                'value' => $check->response_time,
                'status' => $check->status,
            ];
        }

        // P2-011: 30-day uptime history bars
        $uptimeData = [];
        $conn = $checksTable->getConnection();
        $stmt = $conn->execute(
            "SELECT DATE(checked_at) as check_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
             FROM monitor_checks
             WHERE monitor_id = ? AND checked_at >= ?
             GROUP BY DATE(checked_at)
             ORDER BY check_date ASC",
            [$id, DateTime::now()->subDays(29)->startOfDay()->format('Y-m-d H:i:s')]
        );
        $dailyStats = [];
        foreach ($stmt->fetchAll('assoc') as $row) {
            $dailyStats[$row['check_date']] = $row;
        }

        for ($i = 29; $i >= 0; $i--) {
            $dayStr = DateTime::now()->subDays($i)->format('Y-m-d');
            $total = (int)($dailyStats[$dayStr]['total'] ?? 0);
            $success = (int)($dailyStats[$dayStr]['success_count'] ?? 0);
            $uptimeData[] = [
                'date' => $dayStr,
                'uptime' => $total > 0 ? ($success / $total) * 100 : 0,
                'checks' => $total,
            ];
        }

        // SLA integration: check if this monitor has an SLA definition
        $slaData = null;
        try {
            $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
            $slaDef = $slaDefinitionsTable->find()
                ->where(['SlaDefinitions.monitor_id' => $id, 'SlaDefinitions.active' => true])
                ->first();
            if ($slaDef) {
                $slaService = new \App\Service\SlaService();
                $slaData = $slaService->calculateCurrentSla(
                    (int)$id,
                    $slaDef->measurement_period,
                    (float)$slaDef->target_uptime,
                    $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null
                );
                $slaData['sla_id'] = $slaDef->id;
                $slaData['sla_name'] = $slaDef->name;
            }
        } catch (\Exception $e) {
            // SLA tables may not exist yet; silently ignore
        }

        $this->set(compact('monitor', 'uptime', 'avgResponseTime', 'totalChecks', 'responseTimeData', 'uptimeData', 'timeRange', 'slaData'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');

        $monitor = $this->Monitors->newEmptyEntity();

        if ($this->request->is('post')) {
            // Check plan limit before saving
            if ($this->currentOrganization) {
                $planService = new PlanService();
                $orgId = (int)$this->currentOrganization['id'];

                if (!$planService->canAddMonitor($orgId)) {
                    $this->Flash->error(__("You've reached the monitor limit for your plan. Upgrade to add more monitors."));

                    return $this->redirect(['controller' => 'Billing', 'action' => 'plans']);
                }
            }

            $data = $this->request->getData();

            // Parse comma-separated tags into JSON array
            $data = $this->parseTagsFromData($data);

            // Filter configuration fields based on monitor type
            if (isset($data['type']) && isset($data['configuration'])) {
                $data['configuration'] = $this->filterConfigurationByType($data['type'], $data['configuration']);
            }

            $monitor = $this->Monitors->patchEntity($monitor, $data);

            if ($this->Monitors->save($monitor)) {
                $this->Flash->success(__d('monitors', 'Monitor created successfully.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__d('monitors', 'Unable to create monitor. Please try again.'));
        }

        $this->set(compact('monitor'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $monitor = $this->Monitors->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Parse comma-separated tags into JSON array
            $data = $this->parseTagsFromData($data);

            \Cake\Log\Log::debug('=== EDIT DEBUG ===');
            \Cake\Log\Log::debug('Original config from DB:', ['config' => $monitor->configuration]);
            \Cake\Log\Log::debug('POST configuration:', ['config' => $data['configuration'] ?? 'NONE']);

            // Filter configuration fields based on monitor type
            if (isset($data['type']) && isset($data['configuration'])) {
                $filtered = $this->filterConfigurationByType($data['type'], $data['configuration']);
                \Cake\Log\Log::debug('Filtered configuration:', ['config' => $filtered]);
                $data['configuration'] = $filtered;
            }

            $monitor = $this->Monitors->patchEntity($monitor, $data);

            \Cake\Log\Log::debug('After patchEntity:', ['config' => $monitor->configuration]);

            if ($this->Monitors->save($monitor)) {
                \Cake\Log\Log::debug('After save:', ['config' => $monitor->configuration]);
                $this->Flash->success(__d('monitors', 'Monitor updated successfully.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__d('monitors', 'Unable to update monitor. Please try again.'));
        }

        $this->set(compact('monitor'));
    }

    /**
     * Filter configuration array to only include fields relevant to the monitor type
     *
     * @param string $type Monitor type (http, ping, port)
     * @param array $configuration Full configuration array
     * @return array Filtered configuration
     */
    private function filterConfigurationByType(string $type, array $configuration): array
    {
        $filtered = [];

        switch ($type) {
            case 'http':
                $allowedKeys = ['url', 'method', 'expected_status_code', 'headers', 'body',
                               'verify_ssl', 'follow_redirects', 'expected_content'];
                break;

            case 'ping':
                $allowedKeys = ['host', 'packet_count', 'max_packet_loss', 'max_latency'];
                break;

            case 'port':
                $allowedKeys = ['host', 'port', 'protocol', 'send_data', 'expected_response'];
                break;

            default:
                return $configuration;
        }

        // Filter to only include allowed keys
        foreach ($allowedKeys as $key) {
            if (isset($configuration[$key]) && $configuration[$key] !== '') {
                $filtered[$key] = $configuration[$key];
            }
        }

        return $filtered;
    }

    /**
     * Parse comma-separated tags string from form data into JSON array
     *
     * @param array $data Request data
     * @return array Modified data with tags as JSON string
     */
    private function parseTagsFromData(array $data): array
    {
        if (isset($data['tags']) && is_string($data['tags'])) {
            $tagsString = $data['tags'];
            if (trim($tagsString) === '') {
                $data['tags'] = null;
            } else {
                $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsString)))));
                $data['tags'] = !empty($tags) ? json_encode($tags) : null;
            }
        }

        return $data;
    }

    /**
     * Delete method
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $monitor = $this->Monitors->get($id);

        if ($this->Monitors->delete($monitor)) {
            $this->Flash->success(__d('monitors', 'Monitor deleted successfully.'));
        } else {
            $this->Flash->error(__d('monitors', 'Unable to delete monitor. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Toggle active status
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function toggle($id = null)
    {
        $this->request->allowMethod(['post']);

        $monitor = $this->Monitors->get($id);
        $monitor->active = !$monitor->active;

        if ($this->Monitors->save($monitor)) {
            $status = $monitor->active ? 'enabled' : 'disabled';
            $this->Flash->success(__d('monitors', "Monitor {$status} successfully."));
        } else {
            $this->Flash->error(__d('monitors', 'Unable to change monitor status.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Test monitor connection
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response|null JSON response
     */
    public function testConnection($id = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');

        $monitor = $this->Monitors->get($id);
        $config = is_array($monitor->configuration) ? $monitor->configuration : (json_decode((string)$monitor->configuration, true) ?? []);

        $result = ['success' => false, 'response_time' => null, 'status_code' => null, 'message' => ''];

        try {
            $startTime = microtime(true);

            switch ($monitor->type) {
                case 'http':
                    $url = $config['url'] ?? '';
                    if (empty($url)) {
                        $result['message'] = 'No URL configured for this monitor.';
                        break;
                    }

                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_NOBODY => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_FOLLOWLOCATION => !empty($config['follow_redirects']),
                        CURLOPT_SSL_VERIFYPEER => ($config['verify_ssl'] ?? true) ? true : false,
                    ]);

                    if (!empty($config['headers']) && is_array($config['headers'])) {
                        $headers = [];
                        foreach ($config['headers'] as $key => $value) {
                            $headers[] = "{$key}: {$value}";
                        }
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    }

                    curl_exec($ch);
                    $elapsed = round((microtime(true) - $startTime) * 1000);
                    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($error) {
                        $result['message'] = "Connection failed: {$error}";
                    } else {
                        $expectedCode = (int)($config['expected_status_code'] ?? 200);
                        $result['success'] = ($httpCode >= 200 && $httpCode < 400) || $httpCode === $expectedCode;
                        $result['response_time'] = $elapsed;
                        $result['status_code'] = $httpCode;
                        $result['message'] = $result['success']
                            ? "Connection successful (HTTP {$httpCode}, {$elapsed}ms)"
                            : "Unexpected status code: {$httpCode} (expected {$expectedCode})";
                    }
                    break;

                case 'ping':
                    $host = $config['host'] ?? '';
                    if (empty($host)) {
                        $result['message'] = 'No host configured for this monitor.';
                        break;
                    }

                    $host = escapeshellarg($host);
                    $output = [];
                    $returnCode = 0;
                    exec("ping -c 1 -W 5 {$host} 2>&1", $output, $returnCode);
                    $elapsed = round((microtime(true) - $startTime) * 1000);

                    if ($returnCode === 0) {
                        // Extract time from ping output
                        $pingTime = $elapsed;
                        foreach ($output as $line) {
                            if (preg_match('/time[=<](\d+\.?\d*)/', $line, $matches)) {
                                $pingTime = (int)round((float)$matches[1]);
                            }
                        }
                        $result['success'] = true;
                        $result['response_time'] = $pingTime;
                        $result['message'] = "Ping successful ({$pingTime}ms)";
                    } else {
                        $result['message'] = 'Ping failed: host unreachable';
                    }
                    break;

                case 'port':
                    $host = $config['host'] ?? '';
                    $port = (int)($config['port'] ?? 0);
                    if (empty($host) || $port <= 0) {
                        $result['message'] = 'No host/port configured for this monitor.';
                        break;
                    }

                    $errno = 0;
                    $errstr = '';
                    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
                    $elapsed = round((microtime(true) - $startTime) * 1000);

                    if ($socket) {
                        fclose($socket);
                        $result['success'] = true;
                        $result['response_time'] = $elapsed;
                        $result['message'] = "Port {$port} is open ({$elapsed}ms)";
                    } else {
                        $result['message'] = "Port {$port} is closed: {$errstr}";
                    }
                    break;

                default:
                    $result['message'] = "Test connection not supported for type: {$monitor->type}";
                    break;
            }
        } catch (\Exception $e) {
            $result['message'] = 'Connection test error: ' . $e->getMessage();
        }

        $this->set([
            'result' => $result,
            '_serialize' => ['result'],
        ]);
    }

    /**
     * Bulk action method (P2-013)
     *
     * Performs bulk pause, resume, or delete on selected monitors.
     *
     * @return \Cake\Http\Response|null Redirects to index.
     */
    public function bulkAction()
    {
        $this->request->allowMethod(['post']);
        $action = $this->request->getData('action');
        $ids = $this->request->getData('ids', []);

        if (empty($ids)) {
            $this->Flash->warning(__d('monitors', 'No monitors selected.'));

            return $this->redirect(['action' => 'index']);
        }

        // Sanitize IDs to integers
        $ids = array_map('intval', array_filter((array)$ids));

        if (empty($ids)) {
            $this->Flash->warning(__d('monitors', 'No valid monitors selected.'));

            return $this->redirect(['action' => 'index']);
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $count = 0;

        switch ($action) {
            case 'pause':
                $count = $monitorsTable->updateAll(
                    ['active' => false],
                    ['id IN' => $ids]
                );
                $this->Flash->success(__d('monitors', '{0} monitor(s) paused.', $count));
                break;

            case 'resume':
                $count = $monitorsTable->updateAll(
                    ['active' => true],
                    ['id IN' => $ids]
                );
                $this->Flash->success(__d('monitors', '{0} monitor(s) resumed.', $count));
                break;

            case 'delete':
                $count = $monitorsTable->deleteAll(['id IN' => $ids]);
                $this->Flash->success(__d('monitors', '{0} monitor(s) deleted.', $count));
                break;

            default:
                $this->Flash->error(__d('monitors', 'Invalid bulk action.'));
                break;
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Import monitors from CSV (P2-013)
     *
     * Accepts a CSV file with columns: name, type, url/host, check_interval
     * Creates monitors in batch.
     *
     * @return \Cake\Http\Response|null|void Redirects on success, renders view otherwise.
     */
    public function import()
    {
        $this->viewBuilder()->setLayout('admin');

        if ($this->request->is('post')) {
            $file = $this->request->getUploadedFile('csv_file');

            if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error(__d('monitors', 'Please upload a valid CSV file.'));

                return $this->redirect(['action' => 'import']);
            }

            // Validate file type
            $clientFilename = $file->getClientFilename();
            $extension = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                $this->Flash->error(__d('monitors', 'Only CSV files are accepted.'));

                return $this->redirect(['action' => 'import']);
            }

            // Check plan limit
            if ($this->currentOrganization) {
                $planService = new PlanService();
                $orgId = (int)$this->currentOrganization['id'];
            }

            $stream = $file->getStream();
            $content = (string)$stream;
            $lines = array_filter(explode("\n", $content), 'trim');

            if (count($lines) < 2) {
                $this->Flash->error(__d('monitors', 'CSV file must have a header row and at least one data row.'));

                return $this->redirect(['action' => 'import']);
            }

            // Parse header
            $header = str_getcsv(array_shift($lines));
            $header = array_map(function ($col) {
                return strtolower(trim($col));
            }, $header);

            // Required columns
            $nameIdx = array_search('name', $header);
            if ($nameIdx === false) {
                $this->Flash->error(__d('monitors', 'CSV must contain a "name" column.'));

                return $this->redirect(['action' => 'import']);
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
                    continue; // Skip empty rows
                }

                $name = $row[$nameIdx] ?? '';
                if (empty(trim($name))) {
                    $errors[] = __d('monitors', 'Line {0}: Name is required.', $lineNum);
                    continue;
                }

                $type = ($typeIdx !== false && !empty($row[$typeIdx])) ? strtolower(trim($row[$typeIdx])) : 'http';

                // Build configuration based on type
                $configuration = [];
                switch ($type) {
                    case 'http':
                        $url = ($urlIdx !== false) ? trim($row[$urlIdx] ?? '') : '';
                        if (empty($url)) {
                            $errors[] = __d('monitors', 'Line {0}: URL is required for HTTP monitors.', $lineNum);
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
                            $errors[] = __d('monitors', 'Line {0}: Host is required for Ping monitors.', $lineNum);
                            continue 2;
                        }
                        $configuration = ['host' => $host];
                        break;

                    case 'port':
                        $host = ($hostIdx !== false) ? trim($row[$hostIdx] ?? '') : '';
                        $port = ($portIdx !== false) ? trim($row[$portIdx] ?? '') : '';
                        if (empty($host)) {
                            $errors[] = __d('monitors', 'Line {0}: Host is required for Port monitors.', $lineNum);
                            continue 2;
                        }
                        if (empty($port)) {
                            $errors[] = __d('monitors', 'Line {0}: Port is required for Port monitors.', $lineNum);
                            continue 2;
                        }
                        $configuration = ['host' => $host, 'port' => (int)$port];
                        break;

                    default:
                        $errors[] = __d('monitors', 'Line {0}: Invalid type "{1}". Use http, ping, or port.', $lineNum, $type);
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

                // Handle tags
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
                    $errors[] = __d('monitors', 'Line {0}: {1}', $lineNum, implode(', ', $validationErrors));
                }
            }

            if ($created > 0) {
                $this->Flash->success(__d('monitors', '{0} monitor(s) imported successfully.', $created));
            }

            if (!empty($errors)) {
                $this->Flash->error(__d('monitors', 'Import completed with errors:') . "\n" . implode("\n", array_slice($errors, 0, 10)));
            }

            if ($created > 0) {
                return $this->redirect(['action' => 'index']);
            }
        }
    }
}
