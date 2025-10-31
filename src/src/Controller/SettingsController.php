<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SettingService;
use Cake\Http\Exception\BadRequestException;

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
        ];

        foreach ($allSettings as $setting) {
            // Categorize based on key prefix
            if (str_starts_with($setting->key, 'site_') || str_starts_with($setting->key, 'status_page_')) {
                $settings['general'][] = $setting;
            } elseif (str_starts_with($setting->key, 'email_') || str_starts_with($setting->key, 'smtp_')) {
                $settings['email'][] = $setting;
            } elseif (str_starts_with($setting->key, 'monitor_') || str_starts_with($setting->key, 'check_')) {
                $settings['monitoring'][] = $setting;
            } elseif (str_starts_with($setting->key, 'notification_') || str_starts_with($setting->key, 'alert_')) {
                $settings['notifications'][] = $setting;
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
            $this->Flash->error(__('Nenhuma configuração foi enviada.'));
            return $this->redirect(['action' => 'index']);
        }

        $successCount = 0;
        $errorCount = 0;

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
                    } else {
                        $errorCount++;
                    }
                } else {
                    // New setting - auto-detect type
                    if ($this->settingService->set($key, $value)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $this->Flash->success(__("{$successCount} configuração(ões) salva(s) com sucesso."));
        }

        if ($errorCount > 0) {
            $this->Flash->error(__("{$errorCount} configuração(ões) falharam ao salvar."));
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

        // TODO: Implement when EmailService is ready
        $this->Flash->info(__('Funcionalidade de teste de email será implementada quando o serviço de email estiver configurado.'));

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
            $this->Flash->error(__('Categoria não especificada.'));
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
            $this->Flash->success(__("{$resetCount} configuração(ões) restaurada(s) para o padrão."));
        } else {
            $this->Flash->warning(__('Nenhuma configuração foi restaurada.'));
        }

        return $this->redirect(['action' => 'index', '#' => $category]);
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
                'status_page_title' => ['value' => 'System Status', 'type' => 'string'],
                'status_page_public' => ['value' => true, 'type' => 'boolean'],
                'status_page_cache_seconds' => ['value' => 30, 'type' => 'integer'],
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

        return $defaults[$category] ?? [];
    }
}
