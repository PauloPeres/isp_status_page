<?php
declare(strict_types=1);

namespace App\Controller;

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
                    'target LIKE' => '%' . $search . '%',
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

        // Calcular uptime (últimas 24h)
        $checksLast24h = $this->Monitors->MonitorChecks
            ->find()
            ->where([
                'monitor_id' => $id,
                'created >=' => date('Y-m-d H:i:s', strtotime('-24 hours'))
            ])
            ->all();

        $totalChecks = $checksLast24h->count();
        $successfulChecks = $checksLast24h->where(['status' => 'up'])->count();
        $uptime = $totalChecks > 0 ? ($successfulChecks / $totalChecks) * 100 : 0;

        // Calcular tempo médio de resposta
        $avgResponseTime = $checksLast24h->avg('response_time');

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
            $monitor = $this->Monitors->patchEntity($monitor, $this->request->getData());

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
            $monitor = $this->Monitors->patchEntity($monitor, $this->request->getData());

            if ($this->Monitors->save($monitor)) {
                $this->Flash->success(__d('monitors', 'Monitor atualizado com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__d('monitors', 'Não foi possível atualizar o monitor. Por favor, tente novamente.'));
        }

        $this->set(compact('monitor'));
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
