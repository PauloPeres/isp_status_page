<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BackupUploaderService;
use App\Service\SettingService;
use App\Service\EmailService;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\I18n;
use Cake\Cache\Cache;

/**
 * Settings Controller
 *
 * Controller for managing system settings in the admin panel.
 *
 * @property \App\Model\Table\SettingsTable $Settings
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
     * Index method - Display settings page
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Get all settings
        $allSettings = $this->Settings->find()
            ->orderBy(['key' => 'ASC'])
            ->all();

        // Group settings by category
        $settings = [
            'general' => [],
            'email' => [],
            'monitoring' => [],
            'notifications' => [],
            'backup' => [],
        ];

        foreach ($allSettings as $setting) {
            // Categorize based on key prefix
            if (str_starts_with($setting->key, 'site_') || str_starts_with($setting->key, 'status_page_') || $setting->key === 'support_email') {
                $settings['general'][] = $setting;
            } elseif (str_starts_with($setting->key, 'email_') || str_starts_with($setting->key, 'smtp_')) {
                $settings['email'][] = $setting;
            } elseif (str_starts_with($setting->key, 'monitor_') || str_starts_with($setting->key, 'check_')) {
                $settings['monitoring'][] = $setting;
            } elseif (
                str_starts_with($setting->key, 'notification_') ||
                str_starts_with($setting->key, 'alert_') ||
                str_starts_with($setting->key, 'enable_') && str_contains($setting->key, '_alerts')
            ) {
                $settings['notifications'][] = $setting;
            } elseif (str_starts_with($setting->key, 'backup_ftp_')) {
                $settings['backup'][] = $setting;
            }
        }

        $this->set(compact('settings'));
    }

    /**
     * Save method - Save settings
     *
     * @return \Cake\Http\Response|null Redirects on success
     */
    public function save()
    {
        $this->request->allowMethod(['post', 'put']);

        $category = $this->request->getData('category');
        $data = $this->request->getData('settings');

        if (!$data) {
            $this->Flash->error(__d('settings', 'No settings were submitted.'));
            return $this->redirect(['action' => 'index']);
        }

        $successCount = 0;
        $errorCount = 0;
        $languageChanged = false;

        foreach ($data as $key => $value) {
            try {
                // Get existing setting to preserve type
                $existing = $this->Settings->find()
                    ->where(['key' => $key])
                    ->first();

                if ($existing) {
                    // Convert value based on type
                    $typedValue = $this->convertValue($value, $existing->type);

                    if ($this->settingService->set($key, $typedValue, $existing->type)) {
                        $successCount++;

                        // Check if language was changed
                        if ($key === 'site_language') {
                            $languageChanged = true;
                            I18n::setLocale($typedValue);
                        }
                    } else {
                        $errorCount++;
                    }
                } else {
                    // New setting - auto-detect type
                    if ($this->settingService->set($key, $value)) {
                        $successCount++;

                        // Check if language was changed
                        if ($key === 'site_language') {
                            $languageChanged = true;
                            I18n::setLocale($value);
                        }
                    } else {
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        // Clear cache if language was changed
        if ($languageChanged) {
            Cache::clear('default');
            Cache::clear('_cake_core_');
        }

        if ($successCount > 0) {
            $this->Flash->success(__d('settings', "{$successCount} setting(s) saved successfully."));
        }

        if ($errorCount > 0) {
            $this->Flash->error(__d('settings', "{$errorCount} setting(s) failed to save."));
        }

        return $this->redirect(['action' => 'index', '#' => $category ?? '']);
    }

    /**
     * Test email method - Send test email
     *
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function testEmail()
    {
        $this->request->allowMethod(['post']);

        $toEmail = $this->request->getData('test_email');

        if (empty($toEmail)) {
            $this->Flash->error(__d('settings', 'Please provide an email address for the test.'));
            return $this->redirect(['action' => 'index', '#' => 'email']);
        }

        // Validate email format
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->Flash->error(__d('settings', 'Invalid email address.'));
            return $this->redirect(['action' => 'index', '#' => 'email']);
        }

        try {
            $emailService = new EmailService();

            if ($emailService->sendTestEmail($toEmail)) {
                $this->Flash->success(__d('settings', 'Test email sent to {0}. Check your inbox.', $toEmail));
            } else {
                $this->Flash->error(__d('settings', 'Unable to send test email. Please check email settings and error logs.'));
            }
        } catch (\Exception $e) {
            $this->Flash->error(__d('settings', 'Error sending test email: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index', '#' => 'email']);
    }

    /**
     * Reset settings method - Reset to defaults
     *
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function reset()
    {
        $this->request->allowMethod(['post']);

        $category = $this->request->getData('category');

        if (!$category) {
            $this->Flash->error(__d('settings', 'Categoria não especificada.'));
            return $this->redirect(['action' => 'index']);
        }

        // Get default settings for category
        $defaults = $this->getDefaultSettings($category);

        $resetCount = 0;
        foreach ($defaults as $key => $value) {
            if ($this->settingService->set($key, $value['value'], $value['type'])) {
                $resetCount++;
            }
        }

        if ($resetCount > 0) {
            $this->Flash->success(__d('settings', "{$resetCount} setting(s) restored to default."));
        } else {
            $this->Flash->warning(__d('settings', 'No settings were restored.'));
        }

        return $this->redirect(['action' => 'index', '#' => $category]);
    }

    /**
     * Test FTP/SFTP connection
     *
     * Tests the FTP/SFTP connection using settings from the request body
     * (for unsaved form values) or from SettingService (for saved settings).
     * Returns JSON when called via AJAX, otherwise redirects.
     *
     * @return \Cake\Http\Response|null JSON response or redirect
     */
    public function testFtpConnection()
    {
        $this->request->allowMethod(['post']);

        $isAjax = $this->request->is('ajax')
            || $this->request->getHeaderLine('Accept') === 'application/json';

        // Read FTP settings from request body (unsaved form) or fall back to SettingService
        $requestData = $this->request->getData();
        if (!empty($requestData['backup_ftp_host'])) {
            // Override SettingService values with request data so users can test before saving
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
                $this->Flash->success(__d('settings', 'FTP/SFTP connection successful! {0}', $result['message']));
            } else {
                $this->Flash->error(__d('settings', 'FTP/SFTP connection failed: {0}', $result['message']));
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

            $this->Flash->error(__d('settings', 'Erro ao testar conexao: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index', '#' => 'backup']);
    }

    /**
     * Convert value based on type
     *
     * @param mixed $value The value to convert
     * @param string $type The target type
     * @return mixed
     */
    private function convertValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int)$value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => is_array($value) ? $value : json_decode($value, true),
            default => (string)$value,
        };
    }

    /**
     * Get default settings for a category
     *
     * @param string $category Category name
     * @return array
     */
    private function getDefaultSettings(string $category): array
    {
        $defaults = [
            'general' => [
                'site_name' => ['value' => 'ISP Status', 'type' => 'string'],
                'site_url' => ['value' => 'http://localhost:8765', 'type' => 'string'],
                'site_language' => ['value' => 'pt_BR', 'type' => 'string'],
                'status_page_title' => ['value' => 'System Status', 'type' => 'string'],
                'status_page_public' => ['value' => true, 'type' => 'boolean'],
                'status_page_cache_seconds' => ['value' => 30, 'type' => 'integer'],
                'support_email' => ['value' => 'support@example.com', 'type' => 'string'],
            ],
            'email' => [
                'smtp_host' => ['value' => 'localhost', 'type' => 'string'],
                'smtp_port' => ['value' => 587, 'type' => 'integer'],
                'smtp_username' => ['value' => '', 'type' => 'string'],
                'smtp_password' => ['value' => '', 'type' => 'string'],
                'email_from' => ['value' => 'noreply@example.com', 'type' => 'string'],
                'email_from_name' => ['value' => 'ISP Status', 'type' => 'string'],
            ],
            'monitoring' => [
                'monitor_default_interval' => ['value' => 60, 'type' => 'integer'],
                'monitor_default_timeout' => ['value' => 10, 'type' => 'integer'],
                'monitor_max_retries' => ['value' => 3, 'type' => 'integer'],
                'monitor_auto_resolve' => ['value' => true, 'type' => 'boolean'],
            ],
            'notifications' => [
                'notification_email_on_incident_created' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_incident_resolved' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_down' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_up' => ['value' => false, 'type' => 'boolean'],
            ],
        ];

        $defaults['backup'] = [
                'backup_ftp_enabled' => ['value' => false, 'type' => 'boolean'],
                'backup_ftp_type' => ['value' => 'ftp', 'type' => 'string'],
                'backup_ftp_host' => ['value' => '', 'type' => 'string'],
                'backup_ftp_port' => ['value' => 21, 'type' => 'integer'],
                'backup_ftp_username' => ['value' => '', 'type' => 'string'],
                'backup_ftp_password' => ['value' => '', 'type' => 'string'],
                'backup_ftp_path' => ['value' => '/backups', 'type' => 'string'],
                'backup_ftp_passive' => ['value' => true, 'type' => 'boolean'],
            ];

        return $defaults[$category] ?? [];
    }
}
