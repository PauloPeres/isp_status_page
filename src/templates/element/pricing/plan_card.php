<?php
/**
 * Pricing Plan Card Element
 *
 * Reusable pricing card component for both the landing page and billing page.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Plan $plan The plan entity
 *
 * Usage: $this->element('pricing/plan_card', ['plan' => $plan])
 */

$isFree = ($plan->price_monthly == 0 && $plan->slug !== 'enterprise');
$isEnterprise = ($plan->slug === 'enterprise');
$isFeatured = ($plan->slug === 'pro');

// Format prices
if ($isFree) {
    $priceDisplay = '$0';
    $priceSubtitle = 'Free forever';
} elseif ($isEnterprise) {
    $priceDisplay = 'Custom';
    $priceSubtitle = 'Contact sales';
} else {
    $priceDisplay = '$' . number_format($plan->price_monthly / 100, 0);
    $priceSubtitle = null;
    // Calculate yearly savings
    $monthlyTotal = ($plan->price_monthly * 12);
    if ($plan->price_yearly > 0 && $plan->price_yearly < $monthlyTotal) {
        $savingsPercent = round((1 - ($plan->price_yearly / $monthlyTotal)) * 100);
        $yearlyMonthly = '$' . number_format($plan->price_yearly / 100 / 12, 0);
    }
}

// Build feature list
$features = [];

// Monitors
$monitorLabel = ($plan->monitor_limit == -1) ? 'Unlimited monitors' : $plan->monitor_limit . ' monitor' . ($plan->monitor_limit != 1 ? 's' : '');
$features[] = $monitorLabel;

// Check interval
$interval = $plan->check_interval_min;
if ($interval < 60) {
    $features[] = $interval . '-second checks';
} else {
    $features[] = ($interval / 60) . '-minute checks';
}

// Team members
$teamLabel = ($plan->team_member_limit == -1) ? 'Unlimited team members' : $plan->team_member_limit . ' team member' . ($plan->team_member_limit != 1 ? 's' : '');
$features[] = $teamLabel;

// Status pages
$spLabel = ($plan->status_page_limit == -1) ? 'Unlimited status pages' : $plan->status_page_limit . ' status page' . ($plan->status_page_limit != 1 ? 's' : '');
$features[] = $spLabel;

// Features from JSON
$planFeatures = $plan->getFeatures();
$featureLabels = [
    'email_alerts' => 'Email alerts',
    'slack_alerts' => 'Slack & Discord alerts',
    'discord_alerts' => 'Discord alerts',
    'telegram_alerts' => 'Telegram alerts',
    'webhook_alerts' => 'Webhook alerts',
    'sms_alerts' => 'SMS alerts',
    'phone_alerts' => 'Phone call alerts',
    'ssl_monitoring' => 'SSL certificate monitoring',
    'api_access' => 'REST API access',
    'custom_status_page' => 'Custom status pages',
    'custom_domain' => 'Custom domains',
    'multi_region' => 'Multi-region checks',
    'priority_support' => 'Priority support',
    'dedicated_support' => 'Dedicated support',
    'sla_tracking' => 'SLA tracking',
    'sso_saml' => 'SSO / SAML',
];

// Consolidate alert features for cleaner display
$hasAllAlerts = false;
$alertFeatures = ['email_alerts', 'slack_alerts', 'discord_alerts', 'telegram_alerts', 'webhook_alerts', 'sms_alerts', 'phone_alerts'];
$enabledAlerts = 0;
foreach ($alertFeatures as $af) {
    if (is_array($planFeatures) && (in_array($af, $planFeatures) || !empty($planFeatures[$af]))) {
        $enabledAlerts++;
    }
}
if ($enabledAlerts >= 5) {
    $hasAllAlerts = true;
    $features[] = 'All alert channels';
} else {
    foreach ($alertFeatures as $af) {
        if (is_array($planFeatures) && (in_array($af, $planFeatures) || !empty($planFeatures[$af]))) {
            if (isset($featureLabels[$af])) {
                $features[] = $featureLabels[$af];
            }
        }
    }
}

// Non-alert features
$nonAlertFeatures = ['ssl_monitoring', 'api_access', 'custom_status_page', 'custom_domain', 'multi_region', 'priority_support', 'dedicated_support', 'sla_tracking', 'sso_saml'];
foreach ($nonAlertFeatures as $nf) {
    if (is_array($planFeatures) && (in_array($nf, $planFeatures) || !empty($planFeatures[$nf]))) {
        $features[] = $featureLabels[$nf];
    }
}

// Data retention
$features[] = $plan->data_retention_days . '-day data retention';

// API rate limit
if ($plan->api_rate_limit > 0) {
    $features[] = number_format($plan->api_rate_limit) . ' API requests/hour';
}

// CTA
if ($isFree) {
    $ctaText = 'Start Free';
    $ctaUrl = '/app/register';
} elseif ($isEnterprise) {
    $ctaText = 'Contact Sales';
    $ctaUrl = '/contact';
} else {
    $ctaText = 'Start Free Trial';
    $ctaUrl = '/app/register';
}
?>
<div class="plan-card<?= $isFeatured ? ' plan-card--featured' : '' ?><?= $isEnterprise ? ' plan-card--enterprise' : '' ?>">
    <?php if ($isFeatured): ?>
        <div class="plan-card__badge">Most Popular</div>
    <?php endif; ?>

    <div class="plan-card__header">
        <h3 class="plan-card__name"><?= h($plan->name) ?></h3>

        <div class="plan-card__price">
            <span class="plan-card__price-amount"><?= $priceDisplay ?></span>
            <?php if (!$isFree && !$isEnterprise): ?>
                <span class="plan-card__price-period">/mo</span>
            <?php endif; ?>
        </div>

        <?php if ($isFree || $isEnterprise): ?>
            <p class="plan-card__price-subtitle"><?= $priceSubtitle ?></p>
        <?php elseif (isset($savingsPercent) && $savingsPercent > 0): ?>
            <p class="plan-card__price-yearly">
                <?= $yearlyMonthly ?>/mo billed yearly
                <span class="plan-card__savings-badge">Save <?= $savingsPercent ?>%</span>
            </p>
        <?php endif; ?>
    </div>

    <ul class="plan-card__features">
        <?php foreach ($features as $feature): ?>
            <li class="plan-card__feature">
                <svg class="plan-card__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span><?= h($feature) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <a href="<?= $ctaUrl ?>" class="plan-card__cta<?= $isFeatured ? ' plan-card__cta--featured' : '' ?><?= $isEnterprise ? ' plan-card__cta--enterprise' : '' ?>">
        <?= $ctaText ?>
    </a>
</div>
