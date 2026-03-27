<?php
/**
 * Plan Badge Element
 *
 * Displays a small badge showing the current plan (FREE/PRO/BUSINESS) with color.
 *
 * Usage: $this->element('admin/plan_badge', ['plan' => 'pro'])
 *
 * @var \App\View\AppView $this
 * @var string $plan The plan slug (free, pro, business)
 */

$plan = $plan ?? 'free';

$badgeColors = [
    'free' => '#6c757d',
    'pro' => '#1E88E5',
    'business' => '#43A047',
];

$badgeColor = $badgeColors[$plan] ?? '#6c757d';
$badgeLabel = strtoupper($plan);
?>

<span class="plan-badge" style="background-color: <?= h($badgeColor) ?>; color: #fff; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 0.75rem; display: inline-block; letter-spacing: 0.05em;">
    <?= h($badgeLabel) ?>
</span>
