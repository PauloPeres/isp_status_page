<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BackupUploaderService;
use App\Service\EmailService;
use App\Service\SettingService;
use Cake\Cache\Cache;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\I18n;

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
     * Org-level setting keys that customers are allowed to save.
     * Any key not in this list will be rejected by the save() action.
     *
     * @var array<string>
     */
    private const ALLOWED_ORG_KEYS = [
        // General
        'site_name',
        'site_logo_url',
        'site_language',
        'site_timezone',
        'status_page_title',
        'support_email',
        // Notifications
        'enable_email_alerts',
        'notification_email_on_incident_created',
        'notification_email_on_incident_resolved',
        'notification_email_on_down',
        'notification_email_on_up',
        'alert_throttle_minutes',
        'notification_default_cooldown',
        // Channels
        'channel_slack_webhook_url',
        'channel_discord_webhook_url',
        'channel_telegram_bot_token',
        'channel_telegram_chat_id',
        'channel_webhook_url',
        'channel_webhook_secret',
    ];

    /**
     * Index method - Display settings page
     *
     * Simplified for SaaS customers: only General, Notifications, and Channels tabs.
     * Email, Backup, and Monitoring settings are managed by Super Admin.
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

        // Group settings by the 3 customer-facing categories only
        $settings = [
            'general' => [],
            'notifications' => [],
        ];

        foreach ($allSettings as $setting) {
            // General: org identity & display preferences
            if (
                in_array($setting->key, ['site_name', 'site_logo_url', 'site_language', 'site_timezone', 'status_page_title', 'support_email'], true)
            ) {
                $settings['general'][] = $setting;
            // Notifications: alert preferences
            } elseif (
                str_starts_with($setting->key, 'notification_') ||
                str_starts_with($setting->key, 'alert_') ||
                $setting->key === 'enable_email_alerts' ||
                $setting->key === 'notification_default_cooldown'
            ) {
                $settings['notifications'][] = $setting;
            }
            // Email (smtp_*), Backup (backup_ftp_*), Monitoring (monitor_*, check_*)
            // are intentionally excluded — managed by Super Admin
        }

        // Load channel settings as key => value map for the Channels tab
        $channelKeys = [
            'channel_slack_webhook_url',
            'channel_discord_webhook_url',
            'channel_telegram_bot_token',
            'channel_telegram_chat_id',
            'channel_webhook_url',
            'channel_webhook_secret',
        ];
        $settings['channels'] = [];
        foreach ($channelKeys as $key) {
            $settings['channels'][$key] = $this->settingService->getString($key, '');
        }

        $this->set(compact('settings'));
    }

    /**
     * Save method - Save settings
     *
     * Only accepts org-level setting keys. System-level keys (SMTP, FTP, monitoring)
     * are rejected and must be managed via the Super Admin panel.
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
        $rejectedCount = 0;
        $languageChanged = false;

        foreach ($data as $key => $value) {
            // Only allow org-level keys
            if (!in_array($key, self::ALLOWED_ORG_KEYS, true)) {
                $rejectedCount++;
                continue;
            }

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

        if ($rejectedCount > 0) {
            $this->Flash->warning(__d('settings', "{$rejectedCount} setting(s) were rejected (system-level keys cannot be changed here)."));
        }

        return $this->redirect(['action' => 'index', '#' => $category ?? '']);
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
     * Save notification channel settings
     *
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function saveChannels()
    {
        $this->request->allowMethod(['post', 'put']);

        $channelKeys = [
            'channel_slack_webhook_url',
            'channel_discord_webhook_url',
            'channel_telegram_bot_token',
            'channel_telegram_chat_id',
            'channel_webhook_url',
            'channel_webhook_secret',
        ];

        $successCount = 0;
        $data = $this->request->getData();

        foreach ($channelKeys as $key) {
            if (array_key_exists($key, $data)) {
                if ($this->settingService->set($key, (string)$data[$key], 'string')) {
                    $successCount++;
                }
            }
        }

        if ($successCount > 0) {
            $this->Flash->success(__d('settings', "{$successCount} channel setting(s) saved successfully."));
        }

        return $this->redirect(['action' => 'index', '#' => 'channels']);
    }

    /**
     * Test a notification channel by sending a test message
     *
     * @return \Cake\Http\Response JSON response
     */
    public function testNotificationChannel()
    {
        $this->request->allowMethod(['post']);

        $channel = $this->request->getData('channel');
        $data = $this->request->getData();

        $result = ['success' => false, 'message' => ''];

        try {
            switch ($channel) {
                case 'slack':
                    $url = $data['channel_slack_webhook_url'] ?? '';
                    if (empty($url)) {
                        $result['message'] = __d('settings', 'Slack Webhook URL is required.');
                        break;
                    }
                    $payload = json_encode([
                        'text' => 'ISP Status Page - Test notification. Your Slack channel is configured correctly!',
                    ]);
                    $response = $this->sendWebhookRequest($url, $payload, ['Content-Type: application/json']);
                    if ($response['success']) {
                        $result['success'] = true;
                        $result['message'] = __d('settings', 'Test message sent to Slack successfully!');
                    } else {
                        $result['message'] = __d('settings', 'Slack test failed: {0}', $response['error']);
                    }
                    break;

                case 'discord':
                    $url = $data['channel_discord_webhook_url'] ?? '';
                    if (empty($url)) {
                        $result['message'] = __d('settings', 'Discord Webhook URL is required.');
                        break;
                    }
                    $payload = json_encode([
                        'content' => 'ISP Status Page - Test notification. Your Discord channel is configured correctly!',
                    ]);
                    $response = $this->sendWebhookRequest($url, $payload, ['Content-Type: application/json']);
                    if ($response['success']) {
                        $result['success'] = true;
                        $result['message'] = __d('settings', 'Test message sent to Discord successfully!');
                    } else {
                        $result['message'] = __d('settings', 'Discord test failed: {0}', $response['error']);
                    }
                    break;

                case 'telegram':
                    $token = $data['channel_telegram_bot_token'] ?? '';
                    $chatId = $data['channel_telegram_chat_id'] ?? '';
                    if (empty($token) || empty($chatId)) {
                        $result['message'] = __d('settings', 'Telegram Bot Token and Chat ID are required.');
                        break;
                    }
                    $url = "https://api.telegram.org/bot{$token}/sendMessage";
                    $payload = json_encode([
                        'chat_id' => $chatId,
                        'text' => 'ISP Status Page - Test notification. Your Telegram channel is configured correctly!',
                        'parse_mode' => 'HTML',
                    ]);
                    $response = $this->sendWebhookRequest($url, $payload, ['Content-Type: application/json']);
                    if ($response['success']) {
                        $body = json_decode($response['body'] ?? '', true);
                        if (isset($body['ok']) && $body['ok'] === true) {
                            $result['success'] = true;
                            $result['message'] = __d('settings', 'Test message sent to Telegram successfully!');
                        } else {
                            $result['message'] = __d('settings', 'Telegram API error: {0}', $body['description'] ?? 'Unknown error');
                        }
                    } else {
                        $result['message'] = __d('settings', 'Telegram test failed: {0}', $response['error']);
                    }
                    break;

                case 'webhook':
                    $url = $data['channel_webhook_url'] ?? '';
                    if (empty($url)) {
                        $result['message'] = __d('settings', 'Webhook URL is required.');
                        break;
                    }
                    $payload = json_encode([
                        'event' => 'test',
                        'message' => 'ISP Status Page - Test notification. Your webhook is configured correctly!',
                        'timestamp' => date('c'),
                    ]);
                    $headers = ['Content-Type: application/json'];
                    $secret = $data['channel_webhook_secret'] ?? '';
                    if (!empty($secret)) {
                        $signature = hash_hmac('sha256', $payload, $secret);
                        $headers[] = 'X-Signature: ' . $signature;
                    }
                    $response = $this->sendWebhookRequest($url, $payload, $headers);
                    if ($response['success']) {
                        $result['success'] = true;
                        $result['message'] = __d('settings', 'Test message sent to webhook successfully! (HTTP {0})', $response['http_code'] ?? '200');
                    } else {
                        $result['message'] = __d('settings', 'Webhook test failed: {0}', $response['error']);
                    }
                    break;

                default:
                    $result['message'] = __d('settings', 'Unknown channel: {0}', $channel);
                    break;
            }
        } catch (\Exception $e) {
            $result['message'] = __d('settings', 'Error: {0}', $e->getMessage());
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    /**
     * Send a webhook HTTP request via cURL
     *
     * @param string $url Target URL
     * @param string $payload JSON body
     * @param array $headers HTTP headers
     * @return array Result with success, error, http_code, body keys
     */
    private function sendWebhookRequest(string $url, string $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => 0, 'body' => ''];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'error' => '', 'http_code' => $httpCode, 'body' => $body];
        }

        return ['success' => false, 'error' => "HTTP {$httpCode}", 'http_code' => $httpCode, 'body' => $body];
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
     * Only includes org-level categories (General, Notifications).
     * Email, Backup, and Monitoring defaults are managed by Super Admin.
     *
     * @param string $category Category name
     * @return array
     */
    private function getDefaultSettings(string $category): array
    {
        $defaults = [
            'general' => [
                'site_name' => ['value' => 'ISP Status', 'type' => 'string'],
                'site_logo_url' => ['value' => '', 'type' => 'string'],
                'site_language' => ['value' => 'pt_BR', 'type' => 'string'],
                'site_timezone' => ['value' => 'America/Sao_Paulo', 'type' => 'string'],
                'status_page_title' => ['value' => 'System Status', 'type' => 'string'],
                'support_email' => ['value' => 'support@example.com', 'type' => 'string'],
            ],
            'notifications' => [
                'enable_email_alerts' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_incident_created' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_incident_resolved' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_down' => ['value' => true, 'type' => 'boolean'],
                'notification_email_on_up' => ['value' => false, 'type' => 'boolean'],
                'alert_throttle_minutes' => ['value' => 15, 'type' => 'integer'],
                'notification_default_cooldown' => ['value' => 5, 'type' => 'integer'],
            ],
        ];

        return $defaults[$category] ?? [];
    }
}
