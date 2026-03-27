<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiKeyService;
use App\Service\PermissionService;

/**
 * ApiKeys Controller
 *
 * Admin CRUD for managing API keys.
 *
 * @property \App\Model\Table\ApiKeysTable $ApiKeys
 */
class ApiKeysController extends AppController
{
    /**
     * API key service instance
     *
     * @var \App\Service\ApiKeyService
     */
    protected ApiKeyService $apiKeyService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->apiKeyService = new ApiKeyService();
    }

    /**
     * Index method
     *
     * Lists all API keys for the current organization.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission(PermissionService::ACTION_MANAGE_SETTINGS);

        $query = $this->ApiKeys->find()
            ->contain(['Users'])
            ->orderBy(['ApiKeys.created' => 'DESC']);

        $apiKeys = $this->paginate($query);

        $this->set(compact('apiKeys'));
    }

    /**
     * Add method
     *
     * Create a new API key. Shows the plain key once after creation.
     *
     * @return \Cake\Http\Response|null|void Renders view or redirects
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission(PermissionService::ACTION_MANAGE_SETTINGS);

        $plainKey = null;

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $identity = $this->request->getAttribute('identity');
            $userId = $identity ? (int)$identity->getIdentifier() : 0;
            $orgId = (int)$this->currentOrganization['id'];

            // Build permissions array from checkboxes
            $permissions = [];
            if (!empty($data['perm_read'])) {
                $permissions[] = 'read';
            }
            if (!empty($data['perm_write'])) {
                $permissions[] = 'write';
            }
            if (!empty($data['perm_admin'])) {
                $permissions[] = 'admin';
            }

            // Default to read if nothing selected
            if (empty($permissions)) {
                $permissions = ['read'];
            }

            try {
                $result = $this->apiKeyService->generate(
                    $orgId,
                    $userId,
                    $data['name'] ?? '',
                    $permissions
                );

                $plainKey = $result['key'];
                $this->Flash->success(__('API key created successfully. Copy the key now - you will not see it again!'));
                $this->set('newApiKey', $result['entity']);
            } catch (\Exception $e) {
                $this->Flash->error(__('Could not create API key. Please try again.'));
            }
        }

        $this->set('plainKey', $plainKey);
    }

    /**
     * Delete method
     *
     * Revokes and deletes an API key.
     *
     * @param string|null $id API key id
     * @return \Cake\Http\Response|null Redirects to index
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->checkPermission(PermissionService::ACTION_MANAGE_SETTINGS);

        $apiKey = $this->ApiKeys->get($id);

        if ($this->apiKeyService->revoke((int)$apiKey->id)) {
            $this->Flash->success(__('The API key has been revoked.'));
        } else {
            $this->Flash->error(__('The API key could not be revoked. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
