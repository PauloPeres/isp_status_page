<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\SettingService;

/**
 * SettingsController (TASK-NG-009)
 *
 * Read and update organization settings.
 */
class SettingsController extends AppController
{
    private const SENSITIVE_KEYS = [
        'smtp_password',
        'backup_ftp_password',
        'telegram_bot_token',
        'stripe_secret_key',
        'twilio_auth_token',
    ];

    protected SettingService $settingService;

    public function initialize(): void
    {
        parent::initialize();
        $this->settingService = new SettingService();
    }

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

        // Merge system settings with org-level settings (org takes precedence)
        $systemSettings = $this->settingService->getAll();
        $orgSettings = [];
        if ($this->currentOrgId) {
            $orgSettings = $this->settingService->getAllOrg();
        }
        $settings = array_merge($systemSettings, $orgSettings);

        // Mask sensitive values before returning
        foreach (self::SENSITIVE_KEYS as $key) {
            if (isset($settings[$key]) && !empty($settings[$key])) {
                $settings[$key] = '••••••••';
            }
        }

        // Return flat object (frontend expects flat key-value, not wrapped)
        $this->success($settings);
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
            $data = $this->request->getData();

            // Filter out masked sensitive values to prevent overwriting real secrets with placeholder
            foreach (self::SENSITIVE_KEYS as $key) {
                if (isset($data[$key]) && $data[$key] === '••••••••') {
                    unset($data[$key]);
                }
            }

            $this->settingService->saveMultiple($data, $this->currentOrgId);

            $audit = new AuditLogService();
            $audit->log(
                'settings_change',
                $this->currentUserId,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['keys_changed' => array_keys($data)]
            );

            $this->success(['message' => 'Settings saved']);
        } catch (\Exception $e) {
            $this->error('Failed to save settings: ' . $e->getMessage(), 422);
        }
    }
}
