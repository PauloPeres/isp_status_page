<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

use App\Service\BackupUploaderService;
use App\Service\EmailService;
use App\Service\SettingService;

/**
 * Super Admin Settings Controller
 *
 * Manages system-level settings (SMTP, FTP backup, system defaults)
 * that apply platform-wide and are not configurable by customers.
 */
class SettingsController extends AppController
{
    /**
     * Setting service instance
     *
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->settingService = new SettingService();
    }

    /**
     * Index method - Display system settings page with tabs
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $tab = $this->request->getQuery('tab', 'email');

        // Load all system settings
        $settings = [
            // Email / SMTP
            'smtp_host' => $this->settingService->get('smtp_host', ''),
            'smtp_port' => $this->settingService->get('smtp_port', '587'),
            'smtp_username' => $this->settingService->get('smtp_username', ''),
            'smtp_password' => $this->settingService->get('smtp_password', ''),
            'smtp_encryption' => $this->settingService->get('smtp_encryption', 'tls'),
            'email_from_name' => $this->settingService->get('email_from_name', 'ISP Status'),
            'email_from_address' => $this->settingService->get('email_from', 'noreply@example.com'),
            // Backup / FTP
            'backup_ftp_enabled' => $this->settingService->get('backup_ftp_enabled', false),
            'backup_ftp_type' => $this->settingService->get('backup_ftp_type', 'ftp'),
            'backup_ftp_host' => $this->settingService->get('backup_ftp_host', ''),
            'backup_ftp_port' => $this->settingService->get('backup_ftp_port', '21'),
            'backup_ftp_username' => $this->settingService->get('backup_ftp_username', ''),
            'backup_ftp_password' => $this->settingService->get('backup_ftp_password', ''),
            'backup_ftp_path' => $this->settingService->get('backup_ftp_path', '/backups'),
            // System
            'site_name' => $this->settingService->get('site_name', 'ISP Status Page'),
            'default_language' => $this->settingService->get('site_language', 'en'),
            'system_announcement' => $this->settingService->get('system_announcement', ''),
        ];

        $this->set(compact('settings', 'tab'));
    }

    /**
     * Save method - Save system settings
     *
     * @return \Cake\Http\Response|null Redirects on success
     */
    public function save()
    {
        $this->request->allowMethod(['post']);
        $data = $this->request->getData();

        $settingsTable = $this->fetchTable('Settings');
        $savedCount = 0;

        foreach ($data as $key => $value) {
            if ($key === '_csrfToken' || $key === '_tab') {
                continue;
            }

            // Map email_from_address back to email_from key
            if ($key === 'email_from_address') {
                $key = 'email_from';
            }
            // Map default_language back to site_language key
            if ($key === 'default_language') {
                $key = 'site_language';
            }

            // Upsert: find by key, update or create
            $setting = $settingsTable->find()->where(['key' => $key])->first();
            if ($setting) {
                $setting->value = (string)$value;
            } else {
                $setting = $settingsTable->newEntity([
                    'key' => $key,
                    'value' => (string)$value,
                    'type' => $this->detectType($value),
                ]);
            }

            if ($settingsTable->save($setting)) {
                $savedCount++;
            }
        }

        // Clear settings cache
        $this->settingService->clearCache();

        $tab = $this->request->getData('_tab', 'email');
        $this->Flash->success(__("{$savedCount} system setting(s) saved successfully."));

        return $this->redirect(['action' => 'index', '?' => ['tab' => $tab]]);
    }

    /**
     * Test email method - Send test email using system SMTP settings
     *
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function testEmail()
    {
        $this->request->allowMethod(['post']);

        $toEmail = $this->request->getData('test_email');

        if (empty($toEmail)) {
            $this->Flash->error(__('Please provide an email address for the test.'));

            return $this->redirect(['action' => 'index', '?' => ['tab' => 'email']]);
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->Flash->error(__('Invalid email address.'));

            return $this->redirect(['action' => 'index', '?' => ['tab' => 'email']]);
        }

        try {
            $emailService = new EmailService();

            if ($emailService->sendTestEmail($toEmail)) {
                $this->Flash->success(__('Test email sent to {0}. Check your inbox.', $toEmail));
            } else {
                $this->Flash->error(__('Unable to send test email. Please check SMTP settings and error logs.'));
            }
        } catch (\Exception $e) {
            $this->Flash->error(__('Error sending test email: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index', '?' => ['tab' => 'email']]);
    }

    /**
     * Test FTP/SFTP connection using current system settings
     *
     * @return \Cake\Http\Response|null Redirects or returns JSON
     */
    public function testFtp()
    {
        $this->request->allowMethod(['post']);

        $isAjax = $this->request->is('ajax')
            || $this->request->getHeaderLine('Accept') === 'application/json';

        // Read FTP settings from request body (unsaved form) or fall back to SettingService
        $requestData = $this->request->getData();
        if (!empty($requestData['backup_ftp_host'])) {
            foreach ($requestData as $key => $value) {
                if (str_starts_with($key, 'backup_ftp_')) {
                    $this->settingService->set($key, $value);
                }
            }
        }

        try {
            $uploader = new BackupUploaderService($this->settingService);
            $result = $uploader->testConnection();

            if ($isAjax) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => $result['success'],
                        'message' => $result['message'],
                    ]));
            }

            if ($result['success']) {
                $this->Flash->success(__('FTP/SFTP connection successful! {0}', $result['message']));
            } else {
                $this->Flash->error(__('FTP/SFTP connection failed: {0}', $result['message']));
            }
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ]));
            }

            $this->Flash->error(__('Error testing connection: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index', '?' => ['tab' => 'backup']]);
    }

    /**
     * Detect the type of a setting value
     *
     * @param mixed $value The value to detect
     * @return string The detected type
     */
    private function detectType(mixed $value): string
    {
        if (is_bool($value) || $value === '0' || $value === '1' || $value === 'true' || $value === 'false') {
            return 'boolean';
        }
        if (is_numeric($value) && !str_contains((string)$value, '.')) {
            return 'integer';
        }

        return 'string';
    }
}
