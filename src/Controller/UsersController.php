<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to login action
        $this->Authentication->addUnauthenticatedActions(['login']);
    }

    /**
     * Login action
     *
     * @return \Cake\Http\Response|null|void
     */
    public function login()
    {
        // Disable layout - use standalone HTML
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        // If user is logged in redirect them away
        if ($result && $result->isValid()) {
            // Get redirect parameter or default to /admin
            $redirect = $this->request->getQuery('redirect', [
                'controller' => 'Admin',
                'action' => 'index',
            ]);

            return $this->redirect($redirect);
        }

        // Pass result to view only when it's a POST (login attempt)
        if ($this->request->is('post')) {
            $this->set(compact('result'));
        } else {
            $this->set('result', null);
        }
    }

    /**
     * Logout action
     *
     * @return \Cake\Http\Response|null
     */
    public function logout()
    {
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $this->Authentication->logout();
            $this->Flash->success(__('Você saiu com sucesso.'));
        }

        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Index method - List all users
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Users->find()
            ->orderBy(['Users.created' => 'DESC']);

        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id);

        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();

        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Usuário salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Não foi possível salvar o usuário. Tente novamente.'));
        }

        $this->set(compact('user'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Usuário atualizado com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Não foi possível atualizar o usuário. Tente novamente.'));
        }

        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);

        if ($this->Users->delete($user)) {
            $this->Flash->success(__('Usuário excluído com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível excluir o usuário. Tente novamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
