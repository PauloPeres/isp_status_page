<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Plan|null $plan
 * @var string $currentPlan
 */

$this->assign('title', __('Subscription Confirmed'));
?>

<div class="billing-success">
    <div class="success-card">
        <div class="success-icon">&#10003;</div>
        <h1><?= __('Thank You!') ?></h1>
        <p class="success-message">
            <?= __('Your subscription has been confirmed.') ?>
        </p>

        <?php if ($plan): ?>
            <div class="plan-info">
                <span class="plan-label"><?= __('Plan') ?>:</span>
                <span class="plan-value"><?= h($plan->name) ?></span>
            </div>
        <?php endif; ?>

        <p class="success-note">
            <?= __('Your account has been upgraded. You can now enjoy all the features of your new plan.') ?>
        </p>

        <div class="success-actions">
            <?= $this->Html->link(
                __('Go to Dashboard'),
                ['controller' => 'Dashboard', 'action' => 'index'],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= $this->Html->link(
                __('View Plans'),
                ['controller' => 'Billing', 'action' => 'plans'],
                ['class' => 'btn btn-outline']
            ) ?>
        </div>
    </div>
</div>

<style>
.billing-success {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 2rem;
}

.success-card {
    text-align: center;
    max-width: 500px;
    background: var(--color-bg, #fff);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: 12px;
    padding: 3rem 2rem;
}

.success-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: var(--color-success, #43A047);
    color: #fff;
    font-size: 2rem;
    border-radius: 50%;
    margin-bottom: 1.5rem;
}

.success-card h1 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    color: var(--color-text, #333);
}

.success-message {
    font-size: 1.1rem;
    color: var(--color-text-muted, #6c757d);
    margin-bottom: 1.5rem;
}

.plan-info {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--color-bg-secondary, #f5f5f5);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.plan-label {
    color: var(--color-text-muted, #6c757d);
}

.plan-value {
    font-weight: 700;
    color: var(--color-primary, #1E88E5);
    font-size: 1.1rem;
}

.success-note {
    color: var(--color-text-muted, #6c757d);
    font-size: 0.9rem;
    margin-bottom: 2rem;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.success-actions .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}

.success-actions .btn-primary {
    background: var(--color-primary, #1E88E5);
    color: #fff;
    border: none;
}

.success-actions .btn-outline {
    background: transparent;
    color: var(--color-text, #333);
    border: 2px solid var(--color-border, #ccc);
}
</style>

<script>
// Auto-redirect to dashboard after 10 seconds
setTimeout(function() {
    window.location.href = '<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>';
}, 10000);
</script>
