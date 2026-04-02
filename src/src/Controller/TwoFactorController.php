<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\TwoFactorService;
use Authentication\PasswordHasher\DefaultPasswordHasher;
/**
 * TwoFactorController
 *
 * Handles 2FA verification during login (server-side rendered).
 * Setup and management are handled by the Angular SPA via API v2.
 */
class TwoFactorController extends AppController
{
    /**
     * @var \App\Service\TwoFactorService
     */
    private TwoFactorService $twoFactorService;

    /**
     * Initialization hook.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->twoFactorService = new TwoFactorService();
    }

    /**
     * Before filter callback.
     *
     * Allow the verify action without full authentication (the user has
     * authenticated with password but not yet completed 2FA).
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions(['verify']);
    }

    /**
     * Setup 2FA - redirect to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function setup()
    {
        return $this->redirect('/app/settings/security');
    }

    /**
     * Verify 2FA code after login.
     *
     * GET: Show the 2FA code entry form.
     * POST: Verify the code and complete login.
     *
     * This action remains server-side rendered because it is part of the
     * authentication flow before the user has access to the Angular SPA.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function verify()
    {
        // Disable layout - use standalone HTML like the login page
        $this->viewBuilder()->disableAutoLayout();

        $session = $this->request->getSession();
        $pendingUserId = $session->read('pending_2fa_user_id');

        if (!$pendingUserId) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('post')) {
            $code = trim((string)$this->request->getData('code'));
            $useRecovery = (bool)$this->request->getData('use_recovery');

            $usersTable = $this->fetchTable('Users');

            try {
                $user = $usersTable->get($pendingUserId);
            } catch (\Exception $e) {
                $session->delete('pending_2fa_user_id');

                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }

            $verified = false;

            if ($useRecovery) {
                // Verify recovery code
                $hashedCodes = json_decode($user->two_factor_recovery_codes ?? '[]', true);
                $matchIndex = $this->twoFactorService->verifyRecoveryCode($code, $hashedCodes);

                if ($matchIndex >= 0) {
                    // Remove used recovery code
                    unset($hashedCodes[$matchIndex]);
                    $hashedCodes = array_values($hashedCodes);
                    $user->two_factor_recovery_codes = json_encode($hashedCodes);
                    $user->setAccess('two_factor_recovery_codes', true);
                    $usersTable->save($user);
                    $verified = true;
                }
            } else {
                // Verify TOTP code
                $verified = $this->twoFactorService->verifyCode($user->two_factor_secret, $code);
            }

            if ($verified) {
                // Clear pending 2FA state
                $session->delete('pending_2fa_user_id');

                // Set the authentication identity to complete login
                $this->Authentication->setIdentity($user);

                $this->Flash->success(__('Login successful.'));

                return $this->redirect('/app/dashboard');
            }

            $this->set('error', __('Invalid code. Please try again.'));
        }

        $this->set('showRecovery', (bool)$this->request->getData('use_recovery'));
    }

    /**
     * Disable 2FA - POST action that processes and redirects.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function disable()
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($identity->getIdentifier());

        if (!$user->two_factor_enabled) {
            $this->Flash->warning(__('Two-factor authentication is not enabled.'));

            return $this->redirect('/app/settings/security');
        }

        $password = (string)$this->request->getData('password');
        $code = (string)$this->request->getData('code');

        // Verify password
        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($password, $user->password)) {
            $this->Flash->error(__('Invalid password.'));

            return $this->redirect('/app/settings/security');
        }

        // Verify 2FA code
        if (!$this->twoFactorService->verifyCode($user->two_factor_secret, $code)) {
            $this->Flash->error(__('Invalid 2FA code.'));

            return $this->redirect('/app/settings/security');
        }

        // Disable 2FA
        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_recovery_codes = null;

        $user->setAccess('two_factor_secret', true);
        $user->setAccess('two_factor_enabled', true);
        $user->setAccess('two_factor_recovery_codes', true);

        if ($usersTable->save($user)) {
            $this->Flash->success(__('Two-factor authentication has been disabled.'));
        } else {
            $this->Flash->error(__('Could not disable two-factor authentication. Please try again.'));
        }

        return $this->redirect('/app/settings/security');
    }

    /**
     * Recovery codes - redirect to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function recoveryCodes()
    {
        return $this->redirect('/app/settings/security');
    }
}
