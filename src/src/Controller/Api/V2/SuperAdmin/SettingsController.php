<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Service\SettingService;

/**
 * Super Admin SettingsController (TASK-NG-014)
 *
 * Read and update system-wide settings, test email and FTP.
 */
class SettingsController extends AppController
{
    protected SettingService $settingService;

    public function initialize(): void
    {
        parent::initialize();
        $this->settingService = new SettingService();
    }

    /**
     * GET /api/v2/super-admin/settings
     *
     * Return all system-wide settings.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $settings = $this->settingService->getAll();

        $this->success(['settings' => $settings]);
    }

    /**
     * PUT /api/v2/super-admin/settings
     *
     * Save system-wide settings.
     *
     * @return void
     */
    public function save(): void
    {
        $this->request->allowMethod(['put']);

        try {
            $this->settingService->saveMultiple($this->request->getData());

            $this->success(['message' => 'System settings saved']);
        } catch (\Exception $e) {
            $this->error('Failed to save settings: ' . $e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v2/super-admin/settings/test-email
     *
     * Send a test email using current SMTP settings.
     *
     * @return void
     */
    public function testEmail(): void
    {
        $this->request->allowMethod(['post']);

        $to = $this->request->getData('to');
        if (empty($to)) {
            $this->error('Recipient email is required', 400);

            return;
        }

        try {
            $this->settingService->testEmail($to);

            $this->success(['message' => 'Test email sent to ' . $to]);
        } catch (\Exception $e) {
            $this->error('Email test failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v2/super-admin/settings/test-ftp
     *
     * Test FTP/SFTP connection using current backup settings.
     *
     * @return void
     */
    public function testFtp(): void
    {
        $this->request->allowMethod(['post']);

        try {
            $service = new \App\Service\BackupUploaderService();
            $result = $service->testConnection();

            $this->success(['message' => 'FTP connection successful', 'result' => $result]);
        } catch (\Exception $e) {
            $this->error('FTP test failed: ' . $e->getMessage(), 422);
        }
    }
}
