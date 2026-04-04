<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add voice_call_alerts feature to Business plan.
 *
 * Business and Enterprise plans have 'all_alert_channels' which already
 * implies voice_call_alerts via Plan::hasFeature(). This migration
 * explicitly adds the feature for clarity and future-proofing.
 */
class AddVoiceCallAlertsToBusinessPlan extends AbstractMigration
{
    public function up(): void
    {
        // Business: add voice_call_alerts explicitly
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'voice_call_alerts',
            'ssl_monitoring',
            'heartbeat_monitoring',
            'api_access',
            'custom_domains',
            'multi_region',
            'custom_branding',
            'priority_support',
        ]) . "' WHERE slug = 'business'");

        // Enterprise: add voice_call_alerts explicitly
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'voice_call_alerts',
            'ssl_monitoring',
            'heartbeat_monitoring',
            'api_access',
            'custom_domains',
            'multi_region',
            'custom_branding',
            'sla_tracking',
            'sso_saml',
            'dedicated_support',
        ]) . "' WHERE slug = 'enterprise'");
    }

    public function down(): void
    {
        // Revert Business plan
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'ssl_monitoring',
            'heartbeat_monitoring',
            'api_access',
            'custom_domains',
            'multi_region',
            'custom_branding',
            'priority_support',
        ]) . "' WHERE slug = 'business'");

        // Revert Enterprise plan
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'all_alert_channels',
            'ssl_monitoring',
            'heartbeat_monitoring',
            'api_access',
            'custom_domains',
            'multi_region',
            'custom_branding',
            'sla_tracking',
            'sso_saml',
            'dedicated_support',
        ]) . "' WHERE slug = 'enterprise'");
    }
}
