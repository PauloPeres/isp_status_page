<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\SettingService;

/**
 * SettingsController (TASK-NG-009)
 *
 * Read and update organization settings.
 */
class SettingsController extends AppController
{
    /**
     * GET /api/v2/settings
     *
     * Return all settings for the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $service = new SettingService();
        $settings = $service->getAll();

        // Mask sensitive values before returning
        $sensitiveKeys = ['smtp_password', 'backup_ftp_password', 'telegram_bot_token', 'stripe_secret_key', 'twilio_auth_token'];
        foreach ($sensitiveKeys as $key) {
            if (isset($settings[$key]) && !empty($settings[$key])) {
                $settings[$key] = '••••••••';
            }
        }

        $this->success(['settings' => $settings]);
    }

    /**
     * PUT /api/v2/settings
     *
     * Save/update settings for the current organization.
     *
     * @return void
     */
    public function save(): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $service = new SettingService();
            $data = $this->request->getData();

            // Filter out masked sensitive values to prevent overwriting real secrets with placeholder
            $sensitiveKeys = ['smtp_password', 'backup_ftp_password', 'telegram_bot_token', 'stripe_secret_key', 'twilio_auth_token'];
            foreach ($sensitiveKeys as $key) {
                if (isset($data[$key]) && $data[$key] === '••••••••') {
                    unset($data[$key]);
                }
            }

            $service->saveMultiple($data, $this->currentOrgId);

            $this->success(['message' => 'Settings saved']);
        } catch (\Exception $e) {
            $this->error('Failed to save settings: ' . $e->getMessage(), 422);
        }
    }
}
