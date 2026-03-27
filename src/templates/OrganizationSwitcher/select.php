<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\OrganizationUser> $userOrgs
 * @var int|null $currentOrgId
 */
?>

<?php $this->assign('title', __('Select Organization')); ?>

<div class="content-header">
    <h1><?= __('Your Organizations') ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($userOrgs->isEmpty()): ?>
            <p class="text-muted"><?= __('You are not a member of any organization.') ?></p>
        <?php else: ?>
            <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                <?php foreach ($userOrgs as $orgUser): ?>
                    <div class="card" style="border: 2px solid <?= ($orgUser->organization->id ?? null) == $currentOrgId ? '#1E88E5' : '#e0e0e0' ?>; padding: 1.25rem;">
                        <h3 style="margin: 0 0 0.5rem 0;"><?= h($orgUser->organization->name) ?></h3>
                        <p style="color: #666; font-size: 0.875rem; margin: 0 0 0.5rem 0;">
                            <?= __('Role: {0}', ucfirst(h($orgUser->role))) ?>
                            &middot;
                            <?= __('Plan: {0}', ucfirst(h($orgUser->organization->plan))) ?>
                        </p>
                        <?php if (($orgUser->organization->id ?? null) == $currentOrgId): ?>
                            <span class="badge badge-success"><?= __('Current') ?></span>
                        <?php else: ?>
                            <?= $this->Form->postLink(
                                __('Switch to this organization'),
                                ['action' => 'switch', $orgUser->organization->id],
                                ['class' => 'btn btn-primary btn-sm']
                            ) ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
