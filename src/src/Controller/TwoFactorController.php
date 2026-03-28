<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\TwoFactorService;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

/**
 * TwoFactorController
 *
 * Handles 2FA setup, verification, disabling, and recovery code management.
 * Part of TASK-AUTH-MFA.
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
     * Setup 2FA for the current user.
     *
     * GET: Show QR code, secret, and verification form.
     * POST: Verify code, enable 2FA, show recovery codes.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function setup()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get($identity->getIdentifier());

        if ($user->two_factor_enabled) {
            $this->Flash->warning(__('Two-factor authentication is already enabled.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
        }

        $this->request->allowMethod(['get', 'post']);

        // Generate or retrieve the pending secret from session
        $session = $this->request->getSession();

        if ($this->request->is('post')) {
            $code = (string)$this->request->getData('code');
            $secret = $session->read('pending_2fa_secret');

            if (!$secret) {
                $this->Flash->error(__('Session expired. Please start the setup again.'));

                return $this->redirect(['action' => 'setup']);
            }

            if ($this->twoFactorService->verifyCode($secret, $code)) {
                // Generate recovery codes
                $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
                $hashedCodes = $this->twoFactorService->hashRecoveryCodes($recoveryCodes);

                // Enable 2FA on the user
                $user->two_factor_secret = $secret;
                $user->two_factor_enabled = true;
                $user->two_factor_recovery_codes = json_encode($hashedCodes);

                // Bypass accessible fields protection for 2FA fields
                $user->setAccess('two_factor_secret', true);
                $user->setAccess('two_factor_enabled', true);
                $user->setAccess('two_factor_recovery_codes', true);

                if ($usersTable->save($user)) {
                    $session->delete('pending_2fa_secret');
                    $this->Flash->success(__('Two-factor authentication has been enabled.'));
                    $this->set('recoveryCodes', $recoveryCodes);
                    $this->set('setupComplete', true);
                } else {
                    $this->Flash->error(__('Could not enable two-factor authentication. Please try again.'));
                }
            } else {
                $this->Flash->error(__('Invalid verification code. Please try again.'));
            }
        }

        // Generate a new secret if we don't have one pending
        if (!$session->read('pending_2fa_secret')) {
            $secret = $this->twoFactorService->generateSecret();
            $session->write('pending_2fa_secret', $secret);
        } else {
            $secret = $session->read('pending_2fa_secret');
        }

        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user->email, $secret);

        $this->set(compact('secret', 'qrCodeUrl', 'user'));

        if (!isset($recoveryCodes)) {
            $this->set('setupComplete', false);
            $this->set('recoveryCodes', []);
        }
    }

    /**
     * Verify 2FA code after login.
     *
     * GET: Show the 2FA code entry form.
     * POST: Verify the code and complete login.
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

            $usersTable = TableRegistry::getTableLocator()->get('Users');

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

                return $this->redirect(['controller' => 'Admin', 'action' => 'index']);
            }

            $this->set('error', __('Invalid code. Please try again.'));
        }

        $this->set('showRecovery', (bool)$this->request->getData('use_recovery'));
    }

    /**
     * Disable 2FA for the current user.
     *
     * Requires password confirmation and a valid 2FA code.
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

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get($identity->getIdentifier());

        if (!$user->two_factor_enabled) {
            $this->Flash->warning(__('Two-factor authentication is not enabled.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
        }

        $password = (string)$this->request->getData('password');
        $code = (string)$this->request->getData('code');

        // Verify password
        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($password, $user->password)) {
            $this->Flash->error(__('Invalid password.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
        }

        // Verify 2FA code
        if (!$this->twoFactorService->verifyCode($user->two_factor_secret, $code)) {
            $this->Flash->error(__('Invalid 2FA code.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
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

        return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
    }

    /**
     * View and regenerate recovery codes.
     *
     * GET: Show current recovery code count.
     * POST: Regenerate recovery codes (requires password).
     *
     * @return \Cake\Http\Response|null|void
     */
    public function recoveryCodes()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get($identity->getIdentifier());

        if (!$user->two_factor_enabled) {
            $this->Flash->warning(__('Two-factor authentication is not enabled.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'edit', $user->id]);
        }

        $this->request->allowMethod(['get', 'post']);

        $remainingCodes = count(json_decode($user->two_factor_recovery_codes ?? '[]', true));

        if ($this->request->is('post')) {
            $password = (string)$this->request->getData('password');

            // Verify password
            $hasher = new DefaultPasswordHasher();
            if (!$hasher->check($password, $user->password)) {
                $this->Flash->error(__('Invalid password.'));
                $this->set(compact('user', 'remainingCodes'));
                $this->set('recoveryCodes', []);

                return;
            }

            // Generate new recovery codes
            $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
            $hashedCodes = $this->twoFactorService->hashRecoveryCodes($recoveryCodes);

            $user->two_factor_recovery_codes = json_encode($hashedCodes);
            $user->setAccess('two_factor_recovery_codes', true);

            if ($usersTable->save($user)) {
                $remainingCodes = count($hashedCodes);
                $this->Flash->success(__('Recovery codes have been regenerated.'));
                $this->set('recoveryCodes', $recoveryCodes);
            } else {
                $this->Flash->error(__('Could not regenerate recovery codes.'));
                $this->set('recoveryCodes', []);
            }
        } else {
            $this->set('recoveryCodes', []);
        }

        $this->set(compact('user', 'remainingCodes'));
    }
}
