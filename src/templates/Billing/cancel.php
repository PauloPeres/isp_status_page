<?php
/**
 * @var \App\View\AppView $this
 */

$this->assign('title', __('Checkout Cancelled'));
?>

<div class="billing-cancel">
    <div class="cancel-card">
        <div class="cancel-icon">&#10005;</div>
        <h1><?= __('Checkout Cancelled') ?></h1>
        <p class="cancel-message">
            <?= __('Your checkout has been cancelled. No charges have been made.') ?>
        </p>
        <p class="cancel-note">
            <?= __('You can upgrade your plan at any time from the billing page.') ?>
        </p>

        <div class="cancel-actions">
            <?= $this->Html->link(
                __('Back to Plans'),
                ['controller' => 'Billing', 'action' => 'plans'],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= $this->Html->link(
                __('Go to Dashboard'),
                ['controller' => 'Dashboard', 'action' => 'index'],
                ['class' => 'btn btn-outline']
            ) ?>
        </div>
    </div>
</div>

<style>
.billing-cancel {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 2rem;
}

.cancel-card {
    text-align: center;
    max-width: 500px;
    background: var(--color-bg, #fff);
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: 12px;
    padding: 3rem 2rem;
}

.cancel-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: var(--color-warning, #FDD835);
    color: #333;
    font-size: 2rem;
    border-radius: 50%;
    margin-bottom: 1.5rem;
}

.cancel-card h1 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    color: var(--color-text, #333);
}

.cancel-message {
    font-size: 1.1rem;
    color: var(--color-text-muted, #6c757d);
    margin-bottom: 0.5rem;
}

.cancel-note {
    color: var(--color-text-muted, #6c757d);
    font-size: 0.9rem;
    margin-bottom: 2rem;
}

.cancel-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.cancel-actions .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}

.cancel-actions .btn-primary {
    background: var(--color-primary, #1E88E5);
    color: #fff;
    border: none;
}

.cancel-actions .btn-outline {
    background: transparent;
    color: var(--color-text, #333);
    border: 2px solid var(--color-border, #ccc);
}
</style>
