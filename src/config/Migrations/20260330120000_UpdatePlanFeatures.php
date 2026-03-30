<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Update plan features to use array format (for landing page rendering).
 * Ensures features are stored as simple string arrays, not key-value pairs.
 * Also updates Pro plan to include slack_alerts and api_access as array items.
 */
class UpdatePlanFeatures extends AbstractMigration
{
    public function up(): void
    {
        // Update features to array format for each plan
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'email_alerts',
        ]) . "' WHERE slug = 'free'");

        $this->execute("UPDATE plans SET features = '" . json_encode([
            'email_alerts',
            'slack_alerts',
            'api_access',
            'custom_domains',
            'priority_support',
        ]) . "' WHERE slug = 'pro'");

        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'api_access',
            'custom_domains',
            'custom_branding',
            'multi_region',
            'dedicated_support',
        ]) . "' WHERE slug = 'business'");

        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'api_access',
            'custom_domains',
            'custom_branding',
            'multi_region',
            'sso_saml',
            'dedicated_support',
        ]) . "' WHERE slug = 'enterprise'");
    }

    public function down(): void
    {
        // Revert is not critical
    }
}
