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
