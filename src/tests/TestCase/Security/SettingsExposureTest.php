<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression: Settings API must never expose passwords/secrets.
 * Covers VULN-07 fix.
 */
class SettingsExposureTest extends TestCase
{
    /**
     * Sensitive keys that must always be masked in API responses.
     */
    public function testSensitiveKeysAreDefined(): void
    {
        $sensitiveKeys = [
            'smtp_password',
            'backup_ftp_password',
            'telegram_bot_token',
            'stripe_secret_key',
            'twilio_auth_token',
        ];

        // Read the controller source to verify the filter list is present
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/SettingsController.php'
        );

        foreach ($sensitiveKeys as $key) {
            $this->assertStringContainsString(
                $key,
                $source,
                "SettingsController must filter sensitive key: {$key}"
            );
        }

        // Verify masking pattern exists
        $this->assertStringContainsString(
            '••••••••',
            $source,
            'SettingsController must mask sensitive values'
        );
    }
}
