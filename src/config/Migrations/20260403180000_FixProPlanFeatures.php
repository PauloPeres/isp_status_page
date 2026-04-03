<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Fix plan feature flags:
 * - Add webhook_alerts to Pro plan (was missing, causing Pro users to not get webhook access)
 * - Add ssl_monitoring to Pro and above
 * - Add heartbeat_monitoring to Business and above
 * - Add sla_tracking to Enterprise
 * - Ensure feature lists are complete and consistent with billing tiers
 */
class FixProPlanFeatures extends AbstractMigration
{
    public function up(): void
    {
        // Free: email_alerts only
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'email_alerts',
        ]) . "' WHERE slug = 'free'");

        // Pro: email_alerts, slack_alerts, webhook_alerts, ssl_monitoring, api_access, custom_domains, priority_support
        $this->execute("UPDATE plans SET features = '" . json_encode([
            'email_alerts',
            'slack_alerts',
            'webhook_alerts',
            'ssl_monitoring',
            'api_access',
            'custom_domains',
            'priority_support',
        ]) . "' WHERE slug = 'pro'");

        // Business: all_alert_channels, ssl_monitoring, heartbeat_monitoring, api_access, custom_domains, multi_region, custom_branding, priority_support
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

        // Enterprise: all above + sla_tracking, sso_saml, dedicated_support
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

    public function down(): void
    {
        // Revert to previous feature sets
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
}
