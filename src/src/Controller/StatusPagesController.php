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
        return $this->redirect('/app/status-pages');
    }

    public function add()
    {
        return $this->redirect('/app/status-pages/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/status-pages/' . $id . '/edit');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/status-pages/' . $id);
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

    public function delete($id = null)
    {
        return $this->redirect('/app/status-pages');
    }
}
