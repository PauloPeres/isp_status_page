<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuditLogService;
use App\Service\LoginThrottleService;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Audit log service instance.
     *
     * @var \App\Service\AuditLogService
     */
    private AuditLogService $audit;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->audit = new AuditLogService();
    }

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
        // Redirect GET requests to Angular app login
        if ($this->request->is('get')) {
            return $this->redirect('/app/login');
        }

        // Disable layout - use standalone HTML (fallback for POST)
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);

        // TASK-AUTH-005: Brute force protection
        $throttleService = new LoginThrottleService();
        $clientIp = $this->request->clientIp();
        $ua = $this->request->getHeaderLine('User-Agent');

        // Check if IP is locked out before processing authentication
        if ($this->request->is('post') && $throttleService->isLocked($clientIp)) {
            $this->Flash->error(__('Too many failed login attempts. Please try again in 15 minutes.'));
            $this->audit->log('login_locked', null, $clientIp, $ua, ['reason' => 'ip_locked']);
            $this->set('result', null);

            return;
        }

        // Also check by username if provided
        $username = $this->request->getData('username');
        if ($this->request->is('post') && !empty($username) && $throttleService->isLocked($username)) {
            $this->Flash->error(__('Too many failed login attempts for this account. Please try again in 15 minutes.'));
            $this->audit->log('login_locked', null, $clientIp, $ua, ['reason' => 'account_locked', 'username' => $username]);
            $this->set('result', null);

            return;
        }

        $result = $this->Authentication->getResult();

        // If user is logged in redirect them away
        if ($result && $result->isValid()) {
            // TASK-AUTH-005: Clear login attempts on successful login
            $throttleService->clearAttempts($clientIp);
            if (!empty($username)) {
                $throttleService->clearAttempts($username);
            }

            // Regenerate session ID to prevent session fixation attacks (TASK-AUTH-006)
            $this->request->getSession()->renew();

            // Handle "Remember me" checkbox (TASK-AUTH-012)
            if ($this->request->getData('remember_me')) {
                $this->request->getSession()->write('Config.timeout', 60 * 24 * 30); // 30 days
                ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30);
            }

            $user = $this->Authentication->getIdentity();

            // TASK-AUTH-018: Audit log successful login
            $this->audit->log('login_success', $user ? (int)$user->id : null, $clientIp, $ua);

            // TASK-AUTH-MFA: Check if user has 2FA enabled
            if ($user) {
                try {
                    $usersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');
                    $userEntity = $usersTable->find()
                        ->select(['id', 'two_factor_enabled'])
                        ->where(['id' => $user->getIdentifier()])
                        ->disableHydration()
                        ->first();

                    if ($userEntity && !empty($userEntity['two_factor_enabled'])) {
                        // Store user ID for 2FA verification, then logout to prevent access
                        $pendingUserId = $user->getIdentifier();
                        $this->Authentication->logout();
                        $this->request->getSession()->write('pending_2fa_user_id', $pendingUserId);

                        return $this->redirect('/two-factor/verify');
                    }
                } catch (\Exception $e) {
                    // Column may not exist yet; proceed without 2FA check
                }
            }

            // Check if user needs to change password
            if ($user && $user->force_password_change) {
                $this->Flash->warning(__('For security, you must change your password before continuing.'));
                return $this->redirect(['action' => 'changePassword']);
            }

            // Get redirect parameter or default to /app/dashboard (TASK-AUTH-009: validate redirect to prevent open redirect)
            $redirect = $this->request->getQuery('redirect', '/app/dashboard');
            if (!is_string($redirect) || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
                $redirect = '/app/dashboard';
            }

            return $this->redirect($redirect);
        }

        // Pass result to view only when it's a POST (login attempt)
        if ($this->request->is('post')) {
            // TASK-AUTH-005: Record failed login attempt
            $throttleService->recordFailure($clientIp);
            if (!empty($username)) {
                $throttleService->recordFailure($username);
            }

            // TASK-AUTH-018: Audit log failed login
            $this->audit->log('login_failed', null, $clientIp, $ua, ['username' => $username]);

            $remaining = $throttleService->getRemainingAttempts($clientIp);
            if ($remaining > 0 && $remaining <= 3) {
                $this->Flash->warning(__('Warning: {0} login attempts remaining before lockout.', $remaining));
            }

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
            $this->Flash->success(__('You have successfully logged out.'));
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
                $this->Flash->error(__('Please enter your email.'));
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
                    // TASK-AUTH-018: Audit log password reset request
                    $this->audit->log(
                        'password_reset_requested',
                        (int)$user->id,
                        $this->request->clientIp(),
                        $this->request->getHeaderLine('User-Agent'),
                        ['email' => $email]
                    );

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
                                'If the email provided is registered, you will receive instructions to reset your password.'
                            ));
                        }

                        // Log error for debugging
                        $this->log("Failed to send password reset email to {$user->email}: " .
                            ($result['technical_error'] ?? $result['message']), 'error');

                        // Log that the reset link was generated (do not log the actual token)
                        $this->log("Password reset link generated for user {$user->email} (email delivery failed)", 'info');
                    }
                } else {
                    $this->Flash->error(__('Error processing request. Please try again.'));
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $this->Flash->success(__(
                    'If the email provided is registered, you will receive instructions to reset your password.'
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
            $this->Flash->error(__('Invalid reset token.'));
            return $this->redirect(['action' => 'login']);
        }

        // Find user by reset token
        $user = $this->Users->find()
            ->where(['reset_token' => $token])
            ->first();

        if (!$user) {
            $this->Flash->error(__('Invalid or expired reset token.'));
            return $this->redirect(['action' => 'login']);
        }

        // Check if token is still valid
        if (!$user->isResetTokenValid()) {
            $this->Flash->error(__('Reset token expired. Please request a new link.'));
            return $this->redirect(['action' => 'forgotPassword']);
        }

        if ($this->request->is('post')) {
            $password = $this->request->getData('password');
            $confirmPassword = $this->request->getData('confirm_password');

            // Validate passwords
            if (empty($password) || empty($confirmPassword)) {
                $this->Flash->error(__('Please fill in all fields.'));
                $this->set(compact('token'));
                return;
            }

            if ($password !== $confirmPassword) {
                $this->Flash->error(__('Passwords do not match.'));
                $this->set(compact('token'));
                return;
            }

            if (strlen($password) < 8) {
                $this->Flash->error(__('Password must be at least 8 characters.'));
                $this->set(compact('token'));
                return;
            }

            // Update password and clear reset token
            $user->password = $password;
            $user->clearResetToken();

            if ($this->Users->save($user)) {
                // TASK-AUTH-018: Audit log password reset completed
                $this->audit->log(
                    'password_reset_completed',
                    (int)$user->id,
                    $this->request->clientIp(),
                    $this->request->getHeaderLine('User-Agent')
                );

                $this->Flash->success(__('Password reset successfully! You can now log in.'));
                return $this->redirect(['action' => 'login']);
            } else {
                $this->Flash->error(__('Error resetting password. Please try again.'));
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
            $this->Flash->error(__('You must be logged in to change your password.'));
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
                $this->Flash->error(__('Current password is incorrect.'));
                return;
            }

            // Validate new passwords
            if (empty($newPassword) || empty($confirmPassword)) {
                $this->Flash->error(__('Please fill in all fields.'));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->Flash->error(__('Passwords do not match.'));
                return;
            }

            if (strlen($newPassword) < 8) {
                $this->Flash->error(__('Password must be at least 8 characters.'));
                return;
            }

            // Check if new password is different from current
            if ($hasher->check($newPassword, $userEntity->password)) {
                $this->Flash->error(__('The new password must be different from the current password.'));
                return;
            }

            // Update password and remove force change flag
            $userEntity->password = $newPassword;
            $userEntity->set('force_password_change', false);

            if ($this->Users->save($userEntity)) {
                // TASK-AUTH-018: Audit log password changed
                $this->audit->log(
                    'password_changed',
                    (int)$user->id,
                    $this->request->clientIp(),
                    $this->request->getHeaderLine('User-Agent')
                );

                $this->Flash->success(__('Password changed successfully! You can now access the system.'));
                return $this->redirect(['controller' => 'Admin', 'action' => 'index']);
            } else {
                $this->Flash->error(__('Error changing password. Please try again.'));
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
        return $this->redirect('/app/users');
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
        return $this->redirect('/app/users/' . $id);
    }

    /**
     * Add method - redirect to Angular user management.
     *
     * @return \Cake\Http\Response
     */
    public function add()
    {
        return $this->redirect('/app/users/new');
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
        return $this->redirect('/app/profile');
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
        return $this->redirect('/app/users');
    }
}
