<?php
declare(strict_types=1);

namespace App\Controller;

use App\Integration\RestApi\RestApiAdapter;
use App\Model\Entity\Integration;
use Cake\Log\Log;

/**
 * Integrations Controller
 *
 * Admin CRUD for external API integrations (IXC, Zabbix, REST API).
 *
 * @property \App\Model\Table\IntegrationsTable $Integrations
 */
class IntegrationsController extends AppController
{
    /**
     * Index method - list all integrations
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $query = $this->Integrations->find();

        // Filter by type
        if ($this->request->getQuery('type')) {
            $query->where(['type' => $this->request->getQuery('type')]);
        }

        // Filter by active status
        if ($this->request->getQuery('active') !== null && $this->request->getQuery('active') !== '') {
            $query->where(['active' => (bool)$this->request->getQuery('active')]);
        }

        // Search by name
        if ($this->request->getQuery('search')) {
            $search = $this->request->getQuery('search');
            $query->where([
                'name LIKE' => '%' . $search . '%',
            ]);
        }

        $integrations = $this->paginate($query->orderBy(['created' => 'DESC']));

        // Statistics
        $stats = [
            'total' => $this->Integrations->find()->count(),
            'active' => $this->Integrations->find()->where(['active' => true])->count(),
            'ixc' => $this->Integrations->find()->where(['type' => 'ixc'])->count(),
            'zabbix' => $this->Integrations->find()->where(['type' => 'zabbix'])->count(),
            'rest_api' => $this->Integrations->find()->where(['type' => 'rest_api'])->count(),
        ];

        $this->set(compact('integrations', 'stats'));
    }

    /**
     * View method - show integration details
     *
     * @param string|null $id Integration id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $integration = $this->Integrations->get($id, contain: ['IntegrationLogs' => function ($q) {
            return $q->orderBy(['created' => 'DESC'])->limit(20);
        }]);

        $this->set(compact('integration'));
    }

    /**
     * Add method - create new integration
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');

        $integration = $this->Integrations->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Build configuration JSON from form fields
            $data['configuration'] = $this->buildConfigurationFromForm($data);

            $integration = $this->Integrations->patchEntity($integration, $data);

            if ($this->Integrations->save($integration)) {
                $this->Flash->success(__('Integracao criada com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Nao foi possivel criar a integracao. Por favor, tente novamente.'));
        }

        $this->set(compact('integration'));
    }

    /**
     * Edit method - update existing integration
     *
     * @param string|null $id Integration id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $integration = $this->Integrations->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Build configuration JSON from form fields
            $data['configuration'] = $this->buildConfigurationFromForm($data);

            $integration = $this->Integrations->patchEntity($integration, $data);

            if ($this->Integrations->save($integration)) {
                $this->Flash->success(__('Integracao atualizada com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Nao foi possivel atualizar a integracao. Por favor, tente novamente.'));
        }

        $this->set(compact('integration'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Integration id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $integration = $this->Integrations->get($id);

        if ($this->Integrations->delete($integration)) {
            $this->Flash->success(__('Integracao excluida com sucesso.'));
        } else {
            $this->Flash->error(__('Nao foi possivel excluir a integracao. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Test connection method (AJAX)
     *
     * Calls the adapter's testConnection() and returns JSON result.
     *
     * @param string|null $id Integration id.
     * @return \Cake\Http\Response|null JSON response
     */
    public function test($id = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');

        try {
            $integration = $this->Integrations->get($id);
            $config = $integration->getConfiguration();

            $result = $this->createAndTestAdapter($integration->type, $config);

            // Update last_sync fields
            $integration->last_sync_at = new \Cake\I18n\DateTime();
            $integration->last_sync_status = $result['success'] ? Integration::SYNC_SUCCESS : Integration::SYNC_ERROR;
            $this->Integrations->save($integration);

            // Log the test
            $this->logIntegrationTest($integration, $result);
        } catch (\Exception $e) {
            Log::error("Integration test failed: {$e->getMessage()}");
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        $this->set([
            'result' => $result,
            '_serialize' => ['result'],
        ]);
    }

    /**
     * Create adapter and test connection based on integration type
     *
     * @param string $type Integration type
     * @param array<string, mixed> $config Integration configuration
     * @return array Test result
     */
    protected function createAndTestAdapter(string $type, array $config): array
    {
        switch ($type) {
            case 'rest_api':
                $adapter = new RestApiAdapter($config);

                return $adapter->testConnection();

            case 'ixc':
            case 'zabbix':
                // These adapters may not be implemented yet
                // Return a descriptive message
                return [
                    'success' => false,
                    'error' => "Adapter para '{$type}' ainda nao implementado",
                ];

            default:
                return [
                    'success' => false,
                    'error' => "Tipo de integracao desconhecido: {$type}",
                ];
        }
    }

    /**
     * Log integration test result
     *
     * @param \App\Model\Entity\Integration $integration Integration entity
     * @param array<string, mixed> $result Test result
     * @return void
     */
    protected function logIntegrationTest(Integration $integration, array $result): void
    {
        try {
            $integrationLogsTable = $this->fetchTable('IntegrationLogs');
            $log = $integrationLogsTable->newEntity([
                'integration_id' => $integration->id,
                'action' => 'test_connection',
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? $result['error'] ?? 'Test completed',
                'details' => json_encode($result),
            ]);
            $integrationLogsTable->save($log);
        } catch (\Exception $e) {
            Log::warning("Failed to log integration test: {$e->getMessage()}");
        }
    }

    /**
     * Build configuration JSON from form data
     *
     * @param array<string, mixed> $data Form data
     * @return array Configuration array
     */
    protected function buildConfigurationFromForm(array $data): array
    {
        $config = [];
        $configFields = [
            'base_url', 'method', 'auth_type', 'api_key',
            'username', 'password', 'timeout', 'headers',
            'test_endpoint', 'api_key_header',
        ];

        foreach ($configFields as $field) {
            if (isset($data['config_' . $field]) && $data['config_' . $field] !== '') {
                $value = $data['config_' . $field];
                // Parse JSON for headers
                if ($field === 'headers' && is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : $value;
                }
                // Cast timeout to int
                if ($field === 'timeout') {
                    $value = (int)$value;
                }
                $config[$field] = $value;
            }
        }

        return $config;
    }
}
