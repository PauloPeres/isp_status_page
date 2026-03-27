<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * MaintenanceWindows Controller
 *
 * Admin CRUD for scheduling maintenance windows.
 *
 * @property \App\Model\Table\MaintenanceWindowsTable $MaintenanceWindows
 */
class MaintenanceWindowsController extends AppController
{
    /**
     * Index method - list all maintenance windows
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $maintenanceWindows = $this->paginate(
            $this->MaintenanceWindows->find()->orderBy(['starts_at' => 'DESC'])
        );

        $this->set(compact('maintenanceWindows'));
    }

    /**
     * Add method - schedule a new maintenance window
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $maintenanceWindow = $this->MaintenanceWindows->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Set organization_id from current context
            if ($this->currentOrganization) {
                $data['organization_id'] = $this->currentOrganization['id'];
            }

            // Set created_by to current user
            $identity = $this->request->getAttribute('identity');
            if ($identity) {
                $data['created_by'] = (int)$identity->getIdentifier();
            }

            // Handle monitor_ids as JSON
            if (isset($data['monitor_ids_list']) && is_array($data['monitor_ids_list'])) {
                $data['monitor_ids'] = json_encode(array_map('intval', $data['monitor_ids_list']));
            }

            $maintenanceWindow = $this->MaintenanceWindows->patchEntity($maintenanceWindow, $data);

            if ($this->MaintenanceWindows->save($maintenanceWindow)) {
                $this->Flash->success(__('The maintenance window has been scheduled.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The maintenance window could not be saved. Please try again.'));
        }

        // Get available monitors for selection
        $monitorsTable = $this->fetchTable('Monitors');
        $monitors = $monitorsTable->find('list', keyField: 'id', valueField: 'name')
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('maintenanceWindow', 'monitors'));
    }

    /**
     * Edit method - update an existing maintenance window
     *
     * @param string|null $id Maintenance window id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $maintenanceWindow = $this->MaintenanceWindows->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Handle monitor_ids as JSON
            if (isset($data['monitor_ids_list']) && is_array($data['monitor_ids_list'])) {
                $data['monitor_ids'] = json_encode(array_map('intval', $data['monitor_ids_list']));
            }

            $maintenanceWindow = $this->MaintenanceWindows->patchEntity($maintenanceWindow, $data);

            if ($this->MaintenanceWindows->save($maintenanceWindow)) {
                $this->Flash->success(__('The maintenance window has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The maintenance window could not be saved. Please try again.'));
        }

        // Get available monitors for selection
        $monitorsTable = $this->fetchTable('Monitors');
        $monitors = $monitorsTable->find('list', keyField: 'id', valueField: 'name')
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('maintenanceWindow', 'monitors'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Maintenance window id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->checkPermission('manage_resources');

        $maintenanceWindow = $this->MaintenanceWindows->get($id);

        if ($this->MaintenanceWindows->delete($maintenanceWindow)) {
            $this->Flash->success(__('The maintenance window has been deleted.'));
        } else {
            $this->Flash->error(__('The maintenance window could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
