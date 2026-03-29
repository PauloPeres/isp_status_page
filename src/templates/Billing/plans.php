<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Plan[] $plans
 * @var string $currentPlan
 * @var array $usage
 * @var array $limits
 * @var \App\Model\Entity\NotificationCredit|null $credits
 * @var \Cake\Collection\CollectionInterface $recentTransactions
 * @var array $monthlyUsage
 */

$this->assign('title', __('Plans & Pricing'));

$planOrder = ['free' => 0, 'pro' => 1, 'business' => 2, 'enterprise' => 3];
$currentPlanOrder = $planOrder[$currentPlan] ?? 0;

$planColors = [
    'free' => 'var(--color-secondary, #6c757d)',
    'pro' => 'var(--color-primary, #1E88E5)',
    'business' => 'var(--color-success, #43A047)',
    'enterprise' => '#1a1a2e',
];

$planFeatures = [
    'free' => [
        '1 monitor',
        '5 minute check interval',
        'Email alerts only',
        '1 shared status page',
        '1 team member',
        '7 days data retention',
    ],
    'pro' => [
        '50 monitors',
        '1 minute check interval',
        'Email + Slack + Webhook alerts',
        '1 custom status page',
        '5 team members',
        'API access (1,000 req/hr)',
        '30 days data retention',
        'SSL monitoring',
    ],
    'business' => [
        'Unlimited monitors',
        '30 second check interval',
        'All alert channels + SMS',
        '5 custom status pages',
        'Unlimited team members',
        'API access (10,000 req/hr)',
        '90 days data retention',
        'SSL monitoring',
        'Priority support',
    ],
    'enterprise' => [
        'Everything in Business, plus:',
        '15-second check intervals',
        'Unlimited status pages',
        'API access (50,000 req/hr)',
        '365-day data retention',
        'SSO / SAML authentication',
        'SLA tracking',
        'Dedicated support manager',
        'Custom domain support',
    ],
];
?>

<div class="billing-plans">
    <div class="plans-header">
        <h1><?= __('Plans & Pricing') ?></h1>
        <p class="plans-subtitle"><?= __('Choose the plan that best fits your monitoring needs.') ?></p>

        <div class="billing-toggle" id="billingToggle">
            <span class="toggle-label <?= 'active' ?>" data-interval="monthly"><?= __('Monthly') ?></span>
            <label class="toggle-switch">
                <input type="checkbox" id="intervalToggle">
                <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label" data-interval="yearly">
                <?= __('Yearly') ?>
                <span class="toggle-badge"><?= __('Save 20%') ?></span>
            </span>
        </div>
    </div>

    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <?php
            $slug = $plan->slug;
            $isCurrent = ($slug === $currentPlan);
            $planOrderVal = $planOrder[$slug] ?? 0;
            $isUpgrade = $planOrderVal > $currentPlanOrder;
            $isDowngrade = $planOrderVal < $currentPlanOrder;
            $color = $planColors[$slug] ?? 'var(--color-primary, #1E88E5)';
            $features = $planFeatures[$slug] ?? [];
            $isPopular = ($slug === 'pro');
            $isEnterprise = ($slug === 'enterprise');
            ?>
            <div class="plan-card <?= $isCurrent ? 'plan-current' : '' ?> <?= $isPopular ? 'plan-popular' : '' ?> <?= $isEnterprise ? 'plan-enterprise' : '' ?>" style="--plan-color: <?= $color ?>">
                <?php if ($isPopular): ?>
                    <div class="plan-popular-badge"><?= __('Most Popular') ?></div>
                <?php endif; ?>

                <?php if ($isCurrent): ?>
                    <div class="plan-current-badge"><?= __('Current Plan') ?></div>
                <?php endif; ?>

                <div class="plan-header">
                    <h2 class="plan-name"><?= h($plan->name) ?></h2>
                    <div class="plan-price">
                        <?php if ($isEnterprise): ?>
                            <span class="price-amount"><?= __('Custom') ?></span>
                            <span class="price-period"><?= __('pricing') ?></span>
                        <?php else: ?>
                            <span class="price-amount" data-monthly="<?= h($plan->getMonthlyPriceFormatted()) ?>" data-yearly="<?= h($plan->getYearlyPriceFormatted()) ?>">
                                <?= $plan->price_monthly === 0 ? __('Free') : h($plan->getMonthlyPriceFormatted()) ?>
                            </span>
                            <?php if ($plan->price_monthly > 0): ?>
                                <span class="price-period" data-monthly="<?= __('/month') ?>" data-yearly="<?= __('/year') ?>"><?= __('/month') ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <ul class="plan-features">
                    <?php foreach ($features as $feature): ?>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <?= h($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="plan-actions">
                    <?php if ($isEnterprise): ?>
                        <a href="/contact/sales" class="btn btn-enterprise plan-btn">
                            <?= __('Contact Sales') ?>
                        </a>
                    <?php elseif ($isCurrent): ?>
                        <?php if ($slug !== 'free'): ?>
                            <?= $this->Form->create(null, [
                                'url' => ['action' => 'portal'],
                                'class' => 'plan-action-form',
                            ]) ?>
                                <button type="submit" class="btn btn-outline plan-btn">
                                    <?= __('Manage Subscription') ?>
                                </button>
                            <?= $this->Form->end() ?>
                        <?php else: ?>
                            <button class="btn btn-outline plan-btn" disabled>
                                <?= __('Current Plan') ?>
                            </button>
                        <?php endif; ?>
                    <?php elseif ($isUpgrade): ?>
                        <?= $this->Form->create(null, [
                            'url' => ['action' => 'checkout', $slug],
                            'class' => 'plan-action-form',
                        ]) ?>
                            <?= $this->Form->hidden('interval', ['value' => 'monthly', 'class' => 'interval-input']) ?>
                            <button type="submit" class="btn btn-primary plan-btn">
                                <?= __('Upgrade to {0}', h($plan->name)) ?>
                            </button>
                        <?= $this->Form->end() ?>
                    <?php elseif ($isDowngrade): ?>
                        <?= $this->Form->create(null, [
                            'url' => ['action' => 'portal'],
                            'class' => 'plan-action-form',
                        ]) ?>
                            <button type="submit" class="btn btn-outline plan-btn">
                                <?= __('Manage Subscription') ?>
                            </button>
                        <?= $this->Form->end() ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($usage)): ?>
    <div class="usage-summary">
        <h3><?= __('Current Usage') ?></h3>
        <div class="usage-grid">
            <div class="usage-item">
                <span class="usage-label"><?= __('Monitors') ?></span>
                <span class="usage-value">
                    <?= h($usage['monitors'] ?? 0) ?> / <?= h($limits['monitors'] ?? 'N/A') ?>
                </span>
            </div>
            <div class="usage-item">
                <span class="usage-label"><?= __('Team Members') ?></span>
                <span class="usage-value">
                    <?= h($usage['team_members'] ?? 0) ?> / <?= h($limits['team_members'] ?? 'N/A') ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($credits): ?>
    <div class="credits-section">
        <h3><?= __('Notification Credits') ?></h3>
        <p class="credits-subtitle"><?= __('Credits are used for SMS and WhatsApp notifications. Free channels (Email, Slack, Discord, Telegram, Webhook) remain unlimited.') ?></p>

        <div class="credits-overview">
            <div class="credits-balance-card">
                <div class="credits-balance-number"><?= h($credits->balance) ?></div>
                <div class="credits-balance-label"><?= __('Credits Available') ?></div>
                <?php if ($credits->monthly_grant > 0): ?>
                    <div class="credits-grant-info">
                        <?= __('%d credits/month included in your %s plan', $credits->monthly_grant, ucfirst($currentPlan)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="credits-stats-card">
                <div class="credits-stat">
                    <span class="credits-stat-value"><?= h($monthlyUsage['used'] ?? 0) ?></span>
                    <span class="credits-stat-label"><?= __('Used This Month') ?></span>
                </div>
                <div class="credits-stat">
                    <span class="credits-stat-value"><?= h($monthlyUsage['granted'] ?? 0) ?></span>
                    <span class="credits-stat-label"><?= __('Granted This Month') ?></span>
                </div>
                <div class="credits-stat">
                    <span class="credits-stat-value"><?= h($monthlyUsage['purchased'] ?? 0) ?></span>
                    <span class="credits-stat-label"><?= __('Purchased This Month') ?></span>
                </div>
            </div>
        </div>

        <?php
        $totalAvailable = $credits->monthly_grant > 0 ? $credits->monthly_grant : max($credits->balance, 1);
        $usedThisMonth = $monthlyUsage['used'] ?? 0;
        $usagePercent = $totalAvailable > 0 ? min(100, round(($usedThisMonth / $totalAvailable) * 100)) : 0;
        ?>
        <div class="credits-usage-bar-container">
            <div class="credits-usage-bar-header">
                <span><?= __('Monthly Usage') ?></span>
                <span><?= h($usedThisMonth) ?> / <?= h($totalAvailable) ?> <?= __('credits') ?></span>
            </div>
            <div class="credits-usage-bar">
                <div class="credits-usage-bar-fill <?= $usagePercent > 80 ? 'usage-high' : ($usagePercent > 50 ? 'usage-medium' : 'usage-low') ?>" style="width: <?= $usagePercent ?>%"></div>
            </div>
        </div>

        <div class="credits-actions">
            <?= $this->Form->create(null, [
                'url' => ['action' => 'purchaseCredits'],
                'class' => 'credits-purchase-form',
            ]) ?>
                <?= $this->Form->hidden('amount', ['value' => 100]) ?>
                <button type="submit" class="btn btn-primary credits-buy-btn">
                    <?= __('Buy 100 Credits') ?> &mdash; $5
                </button>
            <?= $this->Form->end() ?>

            <div class="credits-auto-recharge">
                <label class="auto-recharge-toggle">
                    <input type="checkbox" <?= $credits->auto_recharge ? 'checked' : '' ?> disabled>
                    <span class="toggle-slider-sm"></span>
                    <span class="auto-recharge-label">
                        <?= __('Auto-recharge when balance falls below %d credits', $credits->auto_recharge_threshold) ?>
                    </span>
                </label>
            </div>
        </div>

        <?php if (!$recentTransactions->isEmpty()): ?>
        <div class="credits-transactions">
            <h4><?= __('Recent Transactions') ?></h4>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Amount') ?></th>
                            <th><?= __('Channel') ?></th>
                            <th><?= __('Description') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td>
                                <?php if ($tx->created): ?>
                                    <span class="utc-datetime" data-utc="<?= $tx->created->format('c') ?>">
                                        <?= $tx->created->nice() ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $typeBadge = match ($tx->type) {
                                    'usage' => 'badge-danger',
                                    'purchase' => 'badge-info',
                                    'monthly_grant' => 'badge-success',
                                    'manual_adjustment' => 'badge-warning',
                                    'refund' => 'badge-secondary',
                                    default => 'badge-secondary',
                                };
                                ?>
                                <span class="badge <?= $typeBadge ?>"><?= h(ucfirst(str_replace('_', ' ', $tx->type))) ?></span>
                            </td>
                            <td>
                                <span class="<?= $tx->amount >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx->amount >= 0 ? '+' : '' ?><?= h($tx->amount) ?>
                                </span>
                            </td>
                            <td><?= $tx->channel ? h(ucfirst($tx->channel)) : '-' ?></td>
                            <td><?= h($tx->description ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.billing-plans {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

.plans-header {
    text-align: center;
    margin-bottom: 2rem;
}

.plans-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--color-text, #333);
}

.plans-subtitle {
    color: var(--color-text-muted, #6c757d);
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

.billing-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--color-bg-secondary, #f5f5f5);
    padding: 0.5rem 1rem;
    border-radius: 2rem;
}

.toggle-label {
    font-size: 0.9rem;
    color: var(--color-text-muted, #6c757d);
    cursor: pointer;
    transition: color 0.2s;
}

.toggle-label.active {
    color: var(--color-text, #333);
    font-weight: 600;
}

.toggle-badge {
    display: inline-block;
    background: var(--color-success, #43A047);
    color: #fff;
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 1rem;
    margin-left: 0.25rem;
    font-weight: 600;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: var(--color-secondary, #ccc);
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: #fff;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--color-primary, #1E88E5);
}

input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.plan-card {
    background: var(--color-bg, #fff);
    border: 2px solid var(--color-border, #e0e0e0);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
}

.plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.plan-card.plan-current {
    border-color: var(--plan-color);
    box-shadow: 0 0 0 1px var(--plan-color);
}

.plan-card.plan-popular {
    border-color: var(--color-primary, #1E88E5);
}

.plan-popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-primary, #1E88E5);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 1rem;
    border-radius: 1rem;
    white-space: nowrap;
}

.plan-current-badge {
    position: absolute;
    top: -12px;
    right: 1rem;
    background: var(--plan-color);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    white-space: nowrap;
}

.plan-header {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.plan-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--plan-color);
    margin-bottom: 0.5rem;
}

.plan-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
}

.price-amount {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text, #333);
}

.price-period {
    font-size: 0.9rem;
    color: var(--color-text-muted, #6c757d);
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem;
    flex: 1;
}

.plan-features li {
    padding: 0.4rem 0;
    font-size: 0.9rem;
    color: var(--color-text, #333);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.feature-check {
    color: var(--color-success, #43A047);
    font-weight: 700;
    flex-shrink: 0;
}

.plan-actions {
    margin-top: auto;
}

.plan-action-form {
    width: 100%;
}

.plan-btn {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s, opacity 0.2s;
}

.btn-primary.plan-btn {
    background: var(--color-primary, #1E88E5);
    color: #fff;
    border: none;
}

.btn-primary.plan-btn:hover {
    opacity: 0.9;
}

.btn-outline.plan-btn {
    background: transparent;
    color: var(--color-text, #333);
    border: 2px solid var(--color-border, #ccc);
}

.btn-outline.plan-btn:hover {
    background: var(--color-bg-secondary, #f5f5f5);
}

.btn-outline.plan-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.usage-summary {
    background: var(--color-bg, #fff);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: 12px;
    padding: 1.5rem;
}

.usage-summary h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--color-text, #333);
}

.usage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.usage-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: 8px;
}

.usage-label {
    font-weight: 500;
    color: var(--color-text, #333);
}

.usage-value {
    font-weight: 600;
    color: var(--color-primary, #1E88E5);
}

/* Notification Credits Section */
.credits-section {
    background: var(--color-bg, #fff);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.credits-section h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: var(--color-text, #333);
}

.credits-subtitle {
    color: var(--color-text-muted, #6c757d);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.credits-overview {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.credits-balance-card {
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.credits-balance-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--color-primary, #1E88E5);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.credits-balance-label {
    font-size: 0.9rem;
    color: var(--color-text-muted, #6c757d);
    font-weight: 500;
}

.credits-grant-info {
    margin-top: 0.75rem;
    font-size: 0.8rem;
    color: var(--color-success, #43A047);
    font-weight: 500;
}

.credits-stats-card {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.credits-stat {
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.credits-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text, #333);
}

.credits-stat-label {
    font-size: 0.8rem;
    color: var(--color-text-muted, #6c757d);
    margin-top: 0.25rem;
}

.credits-usage-bar-container {
    margin-bottom: 1.5rem;
}

.credits-usage-bar-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: var(--color-text-muted, #6c757d);
}

.credits-usage-bar {
    height: 8px;
    background: var(--color-bg-secondary, #e0e0e0);
    border-radius: 4px;
    overflow: hidden;
}

.credits-usage-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.credits-usage-bar-fill.usage-low {
    background: var(--color-success, #43A047);
}

.credits-usage-bar-fill.usage-medium {
    background: var(--color-warning, #FDD835);
}

.credits-usage-bar-fill.usage-high {
    background: var(--color-danger, #E53935);
}

.credits-actions {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.credits-buy-btn {
    padding: 0.6rem 1.5rem;
    font-weight: 600;
    border-radius: 8px;
    white-space: nowrap;
}

.credits-auto-recharge {
    display: flex;
    align-items: center;
}

.auto-recharge-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--color-text-muted, #6c757d);
}

.auto-recharge-toggle input {
    width: 36px;
    height: 20px;
    cursor: pointer;
}

.credits-transactions {
    margin-top: 1rem;
}

.credits-transactions h4 {
    margin-bottom: 0.75rem;
    color: var(--color-text, #333);
}

.text-success {
    color: var(--color-success, #43A047);
    font-weight: 600;
}

.text-danger {
    color: var(--color-danger, #E53935);
    font-weight: 600;
}

@media (max-width: 768px) {
    .plans-grid {
        grid-template-columns: 1fr;
    }

    .plans-header h1 {
        font-size: 1.5rem;
    }

    .price-amount {
        font-size: 1.5rem;
    }

    .credits-overview {
        grid-template-columns: 1fr;
    }

    .credits-stats-card {
        grid-template-columns: 1fr;
    }

    .credits-actions {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('intervalToggle');
    var labels = document.querySelectorAll('.toggle-label');
    var priceAmounts = document.querySelectorAll('.price-amount');
    var pricePeriods = document.querySelectorAll('.price-period');
    var intervalInputs = document.querySelectorAll('.interval-input');

    function updatePricing(isYearly) {
        var interval = isYearly ? 'yearly' : 'monthly';

        labels.forEach(function(label) {
            label.classList.toggle('active', label.dataset.interval === interval);
        });

        priceAmounts.forEach(function(el) {
            var price = el.dataset[interval];
            if (price && price !== '$0.00') {
                el.textContent = price;
            }
        });

        pricePeriods.forEach(function(el) {
            var period = el.dataset[interval];
            if (period) {
                el.textContent = period;
            }
        });

        intervalInputs.forEach(function(input) {
            input.value = interval;
        });
    }

    if (toggle) {
        toggle.addEventListener('change', function() {
            updatePricing(this.checked);
        });
    }

    labels.forEach(function(label) {
        label.addEventListener('click', function() {
            var isYearly = this.dataset.interval === 'yearly';
            toggle.checked = isYearly;
            updatePricing(isYearly);
        });
    });
});
</script>
