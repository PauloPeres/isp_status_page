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

        // Allow public access to login, forgot password and reset password actions
        $this->Authentication->addUnauthenticatedActions(['login', 'forgotPassword', 'resetPassword']);
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
            $user = $this->Authentication->getIdentity();

            // Check if user needs to change password
            if ($user && $user->force_password_change) {
                $this->Flash->warning(__('Por segurança, você deve alterar sua senha antes de continuar.'));
                return $this->redirect(['action' => 'changePassword']);
            }

            // Get redirect parameter or default to /admin
            $redirect = $this->request->getQuery('redirect', [
                'controller' => 'Admin',
                'action' => 'index',
            ]);

            return $this->redirect($redirect);
        }

        // Check if at least one user has already changed their password
        $hasUserWithChangedPassword = $this->Users->find()
            ->where(['force_password_change' => false])
            ->count() > 0;

        // Pass result to view only when it's a POST (login attempt)
        if ($this->request->is('post')) {
            $this->set(compact('result', 'hasUserWithChangedPassword'));
        } else {
            $this->set('result', null);
            $this->set(compact('hasUserWithChangedPassword'));
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
     * Forgot password action - Request password reset
     *
     * @return \Cake\Http\Response|null|void
     */
    public function forgotPassword()
    {
        // Disable layout - use standalone HTML
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('post')) {
            $email = $this->request->getData('email');

            if (empty($email)) {
                $this->Flash->error(__('Por favor, informe seu email.'));
                return;
            }

            // Find user by email
            $user = $this->Users->find()
                ->where(['email' => $email, 'active' => true])
                ->first();

            if ($user) {
                // Generate reset token
                $user->generateResetToken(1); // Token expires in 1 hour

                if ($this->Users->save($user)) {
                    // Build reset link
                    $resetLink = \Cake\Routing\Router::url([
                        'controller' => 'Users',
                        'action' => 'resetPassword',
                        $user->reset_token
                    ], true);

                    // Send email via EmailService
                    $emailService = new \App\Service\EmailService();
                    $result = $emailService->sendPasswordReset($user, $resetLink);

                    if ($result['success']) {
                        // Email sent successfully
                        $this->Flash->success(__($result['message']));
                        $this->log("Password reset email sent successfully to {$user->email}", 'info');
                    } else {
                        // Email failed - show error to admin but generic message to user
                        if ($this->Authentication->getIdentity() && $this->Authentication->getIdentity()->isAdmin()) {
                            // Show detailed error to admin
                            $errorMsg = $result['message'];
                            if (isset($result['technical_error'])) {
                                $errorMsg .= " (Erro técnico: {$result['technical_error']})";
                            }
                            $this->Flash->error($errorMsg);
                        } else {
                            // Generic message for regular users (security)
                            $this->Flash->success(__(
                                'Se o email informado estiver cadastrado, você receberá as instruções para redefinir sua senha.'
                            ));
                        }

                        // Log error for debugging
                        $this->log("Failed to send password reset email to {$user->email}: " .
                            ($result['technical_error'] ?? $result['message']), 'error');

                        // Also log the reset link for development/recovery
                        $this->log("Password reset link (email failed): {$resetLink}", 'info');
                    }
                } else {
                    $this->Flash->error(__('Erro ao processar solicitação. Tente novamente.'));
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $this->Flash->success(__(
                    'Se o email informado estiver cadastrado, você receberá as instruções para redefinir sua senha.'
                ));
            }

            return $this->redirect(['action' => 'login']);
        }
    }

    /**
     * Reset password action - Reset password with token
     *
     * @param string|null $token Reset token
     * @return \Cake\Http\Response|null|void
     */
    public function resetPassword($token = null)
    {
        // Disable layout - use standalone HTML
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);

        if (empty($token)) {
            $this->Flash->error(__('Token de redefinição inválido.'));
            return $this->redirect(['action' => 'login']);
        }

        // Find user by reset token
        $user = $this->Users->find()
            ->where(['reset_token' => $token])
            ->first();

        if (!$user) {
            $this->Flash->error(__('Token de redefinição inválido ou expirado.'));
            return $this->redirect(['action' => 'login']);
        }

        // Check if token is still valid
        if (!$user->isResetTokenValid()) {
            $this->Flash->error(__('Token de redefinição expirado. Solicite um novo link.'));
            return $this->redirect(['action' => 'forgotPassword']);
        }

        if ($this->request->is('post')) {
            $password = $this->request->getData('password');
            $confirmPassword = $this->request->getData('confirm_password');

            // Validate passwords
            if (empty($password) || empty($confirmPassword)) {
                $this->Flash->error(__('Por favor, preencha todos os campos.'));
                $this->set(compact('token'));
                return;
            }

            if ($password !== $confirmPassword) {
                $this->Flash->error(__('As senhas não coincidem.'));
                $this->set(compact('token'));
                return;
            }

            if (strlen($password) < 8) {
                $this->Flash->error(__('A senha deve ter no mínimo 8 caracteres.'));
                $this->set(compact('token'));
                return;
            }

            // Update password and clear reset token
            $user->password = $password;
            $user->clearResetToken();

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Senha redefinida com sucesso! Você já pode fazer login.'));
                return $this->redirect(['action' => 'login']);
            } else {
                $this->Flash->error(__('Erro ao redefinir senha. Tente novamente.'));
            }
        }

        $this->set(compact('token'));
    }

    /**
     * Change password action - Force password change on first login
     *
     * @return \Cake\Http\Response|null|void
     */
    public function changePassword()
    {
        // Disable layout - use standalone HTML
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);

        // Get current logged in user
        $user = $this->Authentication->getIdentity();

        if (!$user) {
            $this->Flash->error(__('Você precisa estar logado para trocar a senha.'));
            return $this->redirect(['action' => 'login']);
        }

        // Get full user entity
        $userEntity = $this->Users->get($user->id);

        if ($this->request->is('post')) {
            $currentPassword = $this->request->getData('current_password');
            $newPassword = $this->request->getData('new_password');
            $confirmPassword = $this->request->getData('confirm_password');

            // Validate current password
            $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
            if (!$hasher->check($currentPassword, $userEntity->password)) {
                $this->Flash->error(__('Senha atual incorreta.'));
                return;
            }

            // Validate new passwords
            if (empty($newPassword) || empty($confirmPassword)) {
                $this->Flash->error(__('Por favor, preencha todos os campos.'));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->Flash->error(__('As senhas não coincidem.'));
                return;
            }

            if (strlen($newPassword) < 8) {
                $this->Flash->error(__('A senha deve ter no mínimo 8 caracteres.'));
                return;
            }

            // Check if new password is different from current
            if ($hasher->check($newPassword, $userEntity->password)) {
                $this->Flash->error(__('A nova senha deve ser diferente da senha atual.'));
                return;
            }

            // Update password and remove force change flag
            $userEntity->password = $newPassword;
            $userEntity->force_password_change = false;

            if ($this->Users->save($userEntity)) {
                $this->Flash->success(__('Senha alterada com sucesso! Você já pode acessar o sistema.'));
                return $this->redirect(['controller' => 'Admin', 'action' => 'index']);
            } else {
                $this->Flash->error(__('Erro ao alterar senha. Tente novamente.'));
            }
        }

        $this->set(compact('user'));
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

            $generatePassword = !empty($data['generate_password']);
            $sendInvite = !empty($data['send_invite']);
            $generatedPassword = null;

            // Generate random password if requested
            if ($generatePassword) {
                // Generate secure random password (12 characters with letters, numbers and symbols)
                $generatedPassword = bin2hex(random_bytes(6)); // 12 character hex string
                $data['password'] = $generatedPassword;
                $data['force_password_change'] = true; // Force password change on first login

                $this->log("Generated password for new user: {$data['username']}", 'info');
            } else {
                // Validar confirmação de senha apenas se não for gerada automaticamente
                if (!empty($data['password']) && !empty($data['confirm_password'])) {
                    if ($data['password'] !== $data['confirm_password']) {
                        $this->Flash->error(__('As senhas não coincidem.'));
                        $this->set(compact('user'));
                        return;
                    }
                } elseif (empty($data['password'])) {
                    $this->Flash->error(__('A senha é obrigatória.'));
                    $this->set(compact('user'));
                    return;
                }
            }

            // Remover campos extras antes de salvar
            unset($data['confirm_password'], $data['generate_password'], $data['send_invite']);

            $user = $this->Users->patchEntity($user, $data);

            if ($this->Users->save($user)) {
                $successMessage = __('Usuário criado com sucesso.');

                // Send invitation email if requested
                if ($sendInvite && $generatedPassword) {
                    $emailService = new \App\Service\EmailService();
                    $loginUrl = \Cake\Routing\Router::url([
                        'controller' => 'Users',
                        'action' => 'login'
                    ], true);

                    $result = $emailService->sendUserInvite($user, $generatedPassword, $loginUrl);

                    if ($result['success']) {
                        $successMessage .= ' ' . __('Email de convite enviado com sucesso.');
                        $this->log("Invitation email sent successfully to {$user->email}", 'info');
                    } else {
                        // Show error but don't fail the user creation
                        $this->Flash->warning(__('Usuário criado, mas houve erro ao enviar o email de convite: ') . $result['message']);
                        $this->log("Failed to send invitation email to {$user->email}: " .
                            ($result['technical_error'] ?? $result['message']), 'error');

                        // Log credentials for recovery
                        $this->log("User credentials (email failed): Username={$user->username}, Password={$generatedPassword}", 'info');
                    }
                }

                $this->Flash->success($successMessage);
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
