<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PlanService;

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

        $monitors = $this->paginate($query->orderBy(['created' => 'DESC']));

        // Estatísticas
        $stats = [
            'total' => $this->Monitors->find()->count(),
            'active' => $this->Monitors->find()->where(['active' => true])->count(),
            'online' => $this->Monitors->find()->where(['status' => 'up'])->count(),
            'offline' => $this->Monitors->find()->where(['status' => 'down'])->count(),
        ];

        $this->set(compact('monitors', 'stats'));
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

        $this->set(compact('monitor', 'uptime', 'avgResponseTime', 'totalChecks'));
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

            // Filter configuration fields based on monitor type
            if (isset($data['type']) && isset($data['configuration'])) {
                $data['configuration'] = $this->filterConfigurationByType($data['type'], $data['configuration']);
            }

            $monitor = $this->Monitors->patchEntity($monitor, $data);

            if ($this->Monitors->save($monitor)) {
                $this->Flash->success(__d('monitors', 'Monitor criado com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__d('monitors', 'Não foi possível criar o monitor. Por favor, tente novamente.'));
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
                $this->Flash->success(__d('monitors', 'Monitor atualizado com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__d('monitors', 'Não foi possível atualizar o monitor. Por favor, tente novamente.'));
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
            $this->Flash->success(__d('monitors', 'Monitor excluído com sucesso.'));
        } else {
            $this->Flash->error(__d('monitors', 'Não foi possível excluir o monitor. Por favor, tente novamente.'));
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
            $status = $monitor->active ? 'ativado' : 'desativado';
            $this->Flash->success(__d('monitors', "Monitor {$status} com sucesso."));
        } else {
            $this->Flash->error(__d('monitors', 'Não foi possível alterar o status do monitor.'));
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

        // TODO: Implement actual connection test based on monitor type
        // For now, return mock data
        $result = [
            'success' => true,
            'response_time' => rand(50, 300),
            'status_code' => 200,
            'message' => 'Conexão bem-sucedida',
        ];

        $this->set([
            'result' => $result,
            '_serialize' => ['result']
        ]);
    }
}
