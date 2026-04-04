<?php
/**
 * Trial Expired Email Template
 *
 * @var \App\View\AppView $this
 * @var object $user
 * @var object $org
 * @var bool $monitorsPaused
 * @var string $siteName
 * @var string $upgradeUrl
 */
$this->assign('title', 'Your Trial Has Ended');
?>

<h2>Your Free Trial Has Ended</h2>

<p>Hello <?= h($user->username) ?>,</p>

<p>
    Your 30-day Business plan trial for <strong><?= h($org->name) ?></strong> on <?= h($siteName) ?> has ended.
</p>

<?php if ($monitorsPaused): ?>
<!-- Monitors Paused Warning -->
<div class="warning-box" style="background: #FFF3E0; border-left: 4px solid #E53935; padding: 14px 18px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0;">
        <strong>Some monitors have been paused.</strong><br>
        Your organization had more monitors than the Free plan allows. We have paused the excess monitors to comply with the Free plan limits. Your paused monitors and their data are safe &mdash; they will be reactivated when you upgrade.
    </p>
</div>
<?php endif; ?>

<!-- What Changed -->
<div class="info-box" style="margin: 20px 0;">
    <p style="margin: 0;"><strong>What changed:</strong></p>
    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
        <li>Your plan has been set to <strong>Free</strong></li>
        <li>Check intervals are now limited to the Free plan minimum</li>
        <li>Some advanced features (Slack alerts, API access, etc.) may no longer be available</li>
        <?php if ($monitorsPaused): ?>
        <li>Monitors beyond the Free plan limit have been paused (not deleted)</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Upgrade CTA -->
<p>
    Upgrade now to keep all your Business plan features, unlimited monitors, and 30-second check intervals.
</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $upgradeUrl ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #2979FF; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
        Upgrade Now
    </a>
</p>

<!-- Reassurance -->
<div class="info-box" style="background: #E8F5E9; border-left: 4px solid #43A047; padding: 14px 18px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0;">
        <strong>Your data is safe.</strong><br>
        All your monitors, incidents, and historical data are preserved. Paused monitors will resume immediately when you upgrade to a paid plan.
    </p>
</div>

<p>
    If you have any questions, just reply to this email &mdash; we're here to help.
</p>
