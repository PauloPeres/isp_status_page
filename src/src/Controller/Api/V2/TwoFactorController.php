<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\TwoFactorService;

/**
 * TwoFactorController (TASK-NG-013)
 *
 * Two-factor authentication setup, verify, disable, and recovery codes.
 */
class TwoFactorController extends AppController
{
    protected TwoFactorService $twoFactorService;

    public function initialize(): void
    {
        parent::initialize();
        $this->twoFactorService = new TwoFactorService();
    }

    /**
     * GET|POST /api/v2/two-factor/setup
     *
     * GET: Return a new TOTP secret and QR code URI.
     * POST: Verify the code and enable 2FA.
     *
     * @return void
     */
    public function setup(): void
    {
        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('get')) {
            $service = $this->twoFactorService;
            $secret = $service->generateSecret();

            $user = $this->fetchTable('Users')->get($this->currentUserId);
            $qrUri = $service->getQrCodeUri($secret, $user->email);

            $this->success([
                'secret' => $secret,
                'qr_uri' => $qrUri,
            ]);

            return;
        }

        // POST — verify code and enable
        $code = $this->request->getData('code');
        $secret = $this->request->getData('secret');

        if (empty($code) || empty($secret)) {
            $this->error('Code and secret are required', 400);

            return;
        }

        $service = $this->twoFactorService;
        if (!$service->verifyCode($secret, $code)) {
            $this->error('Invalid verification code', 422);

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($this->currentUserId);
        $user->set('two_factor_secret', $secret);
        $user->set('two_factor_enabled', true);

        $recoveryCodes = $service->generateRecoveryCodes();
        $user->set('two_factor_recovery_codes', json_encode($recoveryCodes));

        if (!$usersTable->save($user)) {
            $this->error('Failed to enable 2FA', 500);

            return;
        }

        $this->success([
            'message' => '2FA enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * POST /api/v2/two-factor/verify
     *
     * Verify a TOTP code for an already-enabled 2FA.
     *
     * @return void
     */
    public function verify(): void
    {
        $this->request->allowMethod(['post']);

        $code = $this->request->getData('code');
        if (empty($code)) {
            $this->error('Code is required', 400);

            return;
        }

        $user = $this->fetchTable('Users')->get($this->currentUserId);
        if (!$user->two_factor_enabled) {
            $this->error('2FA is not enabled', 400);

            return;
        }

        $service = $this->twoFactorService;
        if (!$service->verifyCode($user->two_factor_secret, $code)) {
            $this->error('Invalid code', 422);

            return;
        }

        $this->success(['message' => 'Code verified']);
    }

    /**
     * POST /api/v2/two-factor/disable
     *
     * Disable 2FA for the current user.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->request->allowMethod(['post']);

        $code = $this->request->getData('code');
        if (empty($code)) {
            $this->error('Current 2FA code is required to disable', 400);

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($this->currentUserId);

        if (!$user->two_factor_enabled) {
            $this->error('2FA is not enabled', 400);

            return;
        }

        $service = $this->twoFactorService;
        if (!$service->verifyCode($user->two_factor_secret, $code)) {
            $this->error('Invalid code', 422);

            return;
        }

        $user->set('two_factor_secret', null);
        $user->set('two_factor_enabled', false);
        $user->set('two_factor_recovery_codes', null);

        if (!$usersTable->save($user)) {
            $this->error('Failed to disable 2FA', 500);

            return;
        }

        $this->success(['message' => '2FA disabled']);
    }

    /**
     * GET|POST /api/v2/two-factor/recovery-codes
     *
     * GET: Return current recovery codes.
     * POST: Regenerate recovery codes.
     *
     * @return void
     */
    public function recoveryCodes(): void
    {
        $this->request->allowMethod(['get', 'post']);

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($this->currentUserId);

        if (!$user->two_factor_enabled) {
            $this->error('2FA is not enabled', 400);

            return;
        }

        if ($this->request->is('get')) {
            $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true);
            $this->success(['recovery_codes' => $codes]);

            return;
        }

        // POST — regenerate
        $service = $this->twoFactorService;
        $newCodes = $service->generateRecoveryCodes();
        $user->set('two_factor_recovery_codes', json_encode($newCodes));

        if (!$usersTable->save($user)) {
            $this->error('Failed to regenerate recovery codes', 500);

            return;
        }

        $this->success(['recovery_codes' => $newCodes]);
    }
}
