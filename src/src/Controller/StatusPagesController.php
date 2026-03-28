<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * StatusPages Controller
 *
 * Admin CRUD for custom status pages.
 *
 * @property \App\Model\Table\StatusPagesTable $StatusPages
 */
class StatusPagesController extends AppController
{
    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to the show action (public status page)
        $this->Authentication->addUnauthenticatedActions(['show']);
    }

    /**
     * Index method - list all status pages
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $statusPages = $this->paginate(
            $this->StatusPages->find()->orderBy(['created' => 'DESC'])
        );

        $this->set(compact('statusPages'));
    }

    /**
     * Add method - create a new status page
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $statusPage = $this->StatusPages->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Set organization_id from current context
            if ($this->currentOrganization) {
                $data['organization_id'] = $this->currentOrganization['id'];
            }

            // Handle monitors as JSON
            if (isset($data['monitor_ids']) && is_array($data['monitor_ids'])) {
                $data['monitors'] = json_encode(array_map('intval', $data['monitor_ids']));
            }

            $statusPage = $this->StatusPages->patchEntity($statusPage, $data);

            if ($this->StatusPages->save($statusPage)) {
                $this->Flash->success(__('The status page has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The status page could not be saved. Please try again.'));
        }

        // Get available monitors for selection
        $monitorsTable = $this->fetchTable('Monitors');
        $monitors = $monitorsTable->find('list', keyField: 'id', valueField: 'name')
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('statusPage', 'monitors'));
    }

    /**
     * Edit method - update an existing status page
     *
     * @param string|null $id Status Page id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $statusPage = $this->StatusPages->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Handle monitors as JSON
            if (isset($data['monitor_ids']) && is_array($data['monitor_ids'])) {
                $data['monitors'] = json_encode(array_map('intval', $data['monitor_ids']));
            }

            $statusPage = $this->StatusPages->patchEntity($statusPage, $data);

            if ($this->StatusPages->save($statusPage)) {
                $this->Flash->success(__('The status page has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The status page could not be saved. Please try again.'));
        }

        // Get available monitors for selection
        $monitorsTable = $this->fetchTable('Monitors');
        $monitors = $monitorsTable->find('list', keyField: 'id', valueField: 'name')
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('statusPage', 'monitors'));
    }

    /**
     * View method - view a status page details (admin)
     *
     * @param string|null $id Status Page id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $statusPage = $this->StatusPages->get($id);

        // Load associated monitors
        $monitorIds = $statusPage->getMonitorIds();
        $monitors = [];
        if (!empty($monitorIds)) {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitors = $monitorsTable->find()
                ->where(['id IN' => $monitorIds])
                ->orderBy(['name' => 'ASC'])
                ->all()
                ->toArray();
        }

        $this->set(compact('statusPage', 'monitors'));
    }

    /**
     * Show method - public status page rendered by slug (no auth required)
     *
     * @param string $slug Status Page slug.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function show($slug = null)
    {
        $this->viewBuilder()->setLayout('public');

        $statusPage = $this->StatusPages->find()
            ->where(['slug' => $slug, 'active' => true])
            ->first();

        if ($statusPage === null) {
            throw new \Cake\Http\Exception\NotFoundException(__('Status page not found.'));
        }

        // Password protection check
        if ($statusPage->isPasswordProtected()) {
            $session = $this->request->getSession();
            $sessionKey = 'status_page_auth_' . $statusPage->id;

            if (!$session->read($sessionKey)) {
                if ($this->request->is('post')) {
                    $password = $this->request->getData('password');
                    if ($password === $statusPage->password) {
                        $session->write($sessionKey, true);
                    } else {
                        $this->Flash->error(__('Invalid password.'));
                        $this->set(compact('statusPage'));
                        $this->set('requirePassword', true);

                        return;
                    }
                } else {
                    $this->set(compact('statusPage'));
                    $this->set('requirePassword', true);

                    return;
                }
            }
        }

        // Load associated monitors
        $monitorIds = $statusPage->getMonitorIds();
        $monitors = [];
        if (!empty($monitorIds)) {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitors = $monitorsTable->find()
                ->where(['id IN' => $monitorIds, 'active' => true])
                ->orderBy(['name' => 'ASC'])
                ->all()
                ->toArray();
        }

        // Load incidents if show_incident_history is enabled
        $incidents = [];
        if ($statusPage->show_incident_history && !empty($monitorIds)) {
            $incidentsTable = $this->fetchTable('Incidents');
            $incidents = $incidentsTable->find()
                ->where(['monitor_id IN' => $monitorIds])
                ->orderBy(['created' => 'DESC'])
                ->limit(20)
                ->all()
                ->toArray();
        }

        $this->set(compact('statusPage', 'monitors', 'incidents'));
        $this->set('requirePassword', false);
    }

    /**
     * Delete method
     *
     * @param string|null $id Status Page id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->checkPermission('manage_resources');

        $statusPage = $this->StatusPages->get($id);

        if ($this->StatusPages->delete($statusPage)) {
            $this->Flash->success(__('The status page has been deleted.'));
        } else {
            $this->Flash->error(__('The status page could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
