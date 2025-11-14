<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Settings seed.
 *
 * Creates default application settings.
 * These can be modified later through the admin panel.
 */
class SettingsSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            // Site Settings
            [
                'key' => 'site_name',
                'value' => 'ISP Status',
                'type' => 'string',
                'description' => 'Site name displayed on status page',
                'modified' => $now,
            ],
            [
                'key' => 'site_url',
                'value' => 'http://localhost:8765',
                'type' => 'string',
                'description' => 'Base URL of the status page',
                'modified' => $now,
            ],
            [
                'key' => 'site_logo_url',
                'value' => '',
                'type' => 'string',
                'description' => 'URL of custom logo displayed on status page',
                'modified' => $now,
            ],
            [
                'key' => 'status_page_title',
                'value' => 'System Status',
                'type' => 'string',
                'description' => 'Title shown on status page',
                'modified' => $now,
            ],
            [
                'key' => 'status_page_public',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Is status page publicly accessible',
                'modified' => $now,
            ],
            [
                'key' => 'status_page_cache_seconds',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Cache duration for status page in seconds',
                'modified' => $now,
            ],
            [
                'key' => 'support_email',
                'value' => 'support@example.com',
                'type' => 'string',
                'description' => 'Support email displayed on public status page',
                'modified' => $now,
            ],

            // Email Settings
            [
                'key' => 'email_from',
                'value' => 'noreply@example.com',
                'type' => 'string',
                'description' => 'Email from address',
                'modified' => $now,
            ],
            [
                'key' => 'email_from_name',
                'value' => 'ISP Status',
                'type' => 'string',
                'description' => 'Email from name',
                'modified' => $now,
            ],
            [
                'key' => 'smtp_host',
                'value' => 'smtp.example.com',
                'type' => 'string',
                'description' => 'SMTP server host',
                'modified' => $now,
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'type' => 'integer',
                'description' => 'SMTP server port',
                'modified' => $now,
            ],
            [
                'key' => 'smtp_username',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP username',
                'modified' => $now,
            ],
            [
                'key' => 'smtp_password',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP password (encrypted)',
                'modified' => $now,
            ],
            [
                'key' => 'smtp_encryption',
                'value' => 'tls',
                'type' => 'string',
                'description' => 'SMTP encryption type (TLS, SSL or none)',
                'modified' => $now,
            ],

            // Monitoring Settings
            [
                'key' => 'default_check_interval',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Default check interval in seconds',
                'modified' => $now,
            ],
            [
                'key' => 'default_timeout',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Default timeout in seconds',
                'modified' => $now,
            ],
            [
                'key' => 'default_retry_count',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Default number of retries before marking as down',
                'modified' => $now,
            ],
            [
                'key' => 'check_retention_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Days to retain monitor check history',
                'modified' => $now,
            ],
            [
                'key' => 'log_retention_days',
                'value' => '7',
                'type' => 'integer',
                'description' => 'Days to retain integration logs',
                'modified' => $now,
            ],

            // Alert Settings
            [
                'key' => 'alert_throttle_minutes',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Default minutes between alerts for same monitor',
                'modified' => $now,
            ],
            [
                'key' => 'enable_email_alerts',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable email alerts',
                'modified' => $now,
            ],
            [
                'key' => 'enable_whatsapp_alerts',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable WhatsApp alerts (future)',
                'modified' => $now,
            ],
            [
                'key' => 'enable_telegram_alerts',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable Telegram alerts (future)',
                'modified' => $now,
            ],
            [
                'key' => 'enable_sms_alerts',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable SMS alerts (future)',
                'modified' => $now,
            ],

            // Maintenance Settings
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
                'modified' => $now,
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'We are currently performing maintenance. Please check back soon.',
                'type' => 'string',
                'description' => 'Message shown during maintenance',
                'modified' => $now,
            ],

            // Advanced Settings
            [
                'key' => 'enable_auto_incidents',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Automatically create incidents when monitors go down',
                'modified' => $now,
            ],
            [
                'key' => 'enable_auto_resolve',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Automatically resolve incidents when monitors come back up',
                'modified' => $now,
            ],
            [
                'key' => 'timezone',
                'value' => 'America/Sao_Paulo',
                'type' => 'string',
                'description' => 'Default timezone',
                'modified' => $now,
            ],
            [
                'key' => 'date_format',
                'value' => 'd/m/Y H:i:s',
                'type' => 'string',
                'description' => 'Date format for display',
                'modified' => $now,
            ],
        ];

        $table = $this->table('settings');
        $table->insert($data)->save();
    }
}
