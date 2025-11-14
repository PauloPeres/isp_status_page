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
        $this->viewBuilder()->setLayout('admin');

        $query = $this->Users->find();

        // Filtro por função
        if ($this->request->getQuery('role')) {
            $query->where(['role' => $this->request->getQuery('role')]);
        }

        // Filtro por status ativo/inativo
        if ($this->request->getQuery('active') !== null && $this->request->getQuery('active') !== '') {
            $query->where(['active' => (bool)$this->request->getQuery('active')]);
        }

        // Busca por username ou email
        if ($this->request->getQuery('search')) {
            $search = $this->request->getQuery('search');
            $query->where([
                'OR' => [
                    'username LIKE' => '%' . $search . '%',
                    'email LIKE' => '%' . $search . '%',
                ]
            ]);
        }

        $query->orderBy(['Users.created' => 'DESC']);

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
        $this->viewBuilder()->setLayout('admin');

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
        $this->viewBuilder()->setLayout('admin');

        $user = $this->Users->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Validar confirmação de senha
            if (!empty($data['password']) && !empty($data['confirm_password'])) {
                if ($data['password'] !== $data['confirm_password']) {
                    $this->Flash->error(__('As senhas não coincidem.'));
                    $this->set(compact('user'));
                    return;
                }
            }

            // Remover campo de confirmação antes de salvar
            unset($data['confirm_password']);

            $user = $this->Users->patchEntity($user, $data);

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Usuário criado com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Não foi possível criar o usuário. Verifique os erros abaixo.'));
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
        $this->viewBuilder()->setLayout('admin');

        $user = $this->Users->get($id);

        // Only allow users to edit their own profile
        $identity = $this->Authentication->getIdentity();
        if (!$identity || $identity->id != $user->id) {
            $this->Flash->error(__('Você não tem permissão para editar este usuário.'));

            return $this->redirect(['action' => 'view', $identity->id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Handle password change
            if (!empty($data['new_password'])) {
                // Validate password confirmation
                if ($data['new_password'] !== $data['confirm_password']) {
                    $this->Flash->error(__('As senhas não coincidem.'));
                } else {
                    // Set the new password
                    $data['password'] = $data['new_password'];
                }
            }

            // Remove temporary password fields
            unset($data['new_password'], $data['confirm_password']);

            // Don't allow changing role or active status through profile edit
            unset($data['role'], $data['active']);

            $user = $this->Users->patchEntity($user, $data);

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Perfil atualizado com sucesso.'));

                return $this->redirect(['action' => 'view', $user->id]);
            }

            $this->Flash->error(__('Não foi possível atualizar o perfil. Tente novamente.'));
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
