<?php
/**
 * StatusPages View (Admin)
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 */
$this->assign('title', __('Status Page: {0}', h($statusPage->name)));
?>

<div class="content-header">
    <h1><?= h($statusPage->name) ?></h1>
    <div class="header-actions">
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $statusPage->id], ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<div class="card">
    <h3><?= __('Details') ?></h3>
    <div class="detail-grid">
        <div class="detail-item">
            <span class="detail-label"><?= __('Name') ?></span>
            <span class="detail-value"><?= h($statusPage->name) ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Slug') ?></span>
            <span class="detail-value">
                <a href="<?= $this->Url->build('/s/' . $statusPage->slug) ?>" target="_blank">
                    /s/<?= h($statusPage->slug) ?>
                </a>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Custom Domain') ?></span>
            <span class="detail-value"><?= h($statusPage->custom_domain) ?: __('None') ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Active') ?></span>
            <span class="detail-value">
                <span class="badge <?= $statusPage->active ? 'badge-success' : 'badge-secondary' ?>">
                    <?= $statusPage->active ? __('Yes') : __('No') ?>
                </span>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Password Protected') ?></span>
            <span class="detail-value">
                <span class="badge <?= $statusPage->isPasswordProtected() ? 'badge-warning' : 'badge-secondary' ?>">
                    <?= $statusPage->isPasswordProtected() ? __('Yes') : __('No') ?>
                </span>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Show Uptime Chart') ?></span>
            <span class="detail-value"><?= $statusPage->show_uptime_chart ? __('Yes') : __('No') ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Show Incident History') ?></span>
            <span class="detail-value"><?= $statusPage->show_incident_history ? __('Yes') : __('No') ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Created') ?></span>
            <span class="detail-value"><?= h($statusPage->created->format('Y-m-d H:i:s')) ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __('Modified') ?></span>
            <span class="detail-value"><?= h($statusPage->modified->format('Y-m-d H:i:s')) ?></span>
        </div>
    </div>

    <?php if (!empty($statusPage->header_text)): ?>
        <h3 style="margin-top: 24px;"><?= __('Header Text') ?></h3>
        <div class="content-preview"><?= $statusPage->header_text ?></div>
    <?php endif; ?>

    <?php if (!empty($statusPage->footer_text)): ?>
        <h3 style="margin-top: 24px;"><?= __('Footer Text') ?></h3>
        <div class="content-preview"><?= $statusPage->footer_text ?></div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 24px;">
    <h3><?= __('Associated Monitors') ?></h3>
    <?php if (!empty($monitors)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Type') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Uptime') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitors as $monitor): ?>
                    <tr>
                        <td><?= h($monitor->name) ?></td>
                        <td><span class="badge badge-info"><?= h(ucfirst($monitor->type)) ?></span></td>
                        <td>
                            <?php
                            $badgeClass = match ($monitor->status) {
                                'up' => 'badge-success',
                                'down' => 'badge-error',
                                'degraded' => 'badge-warning',
                                default => 'badge-secondary',
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= h(ucfirst($monitor->status)) ?></span>
                        </td>
                        <td><?= $monitor->uptime_percentage !== null ? number_format($monitor->uptime_percentage, 2) . '%' : __('N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted"><?= __('No monitors associated with this status page.') ?></p>
    <?php endif; ?>
</div>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    padding: 16px 0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-gray-medium, #666);
}

.detail-value {
    font-size: 15px;
    color: var(--color-dark, #1a1a2e);
}

.content-preview {
    padding: 12px;
    background: var(--color-light, #f8f9fa);
    border-radius: 6px;
    border: 1px solid var(--color-gray-light, #e0e0e0);
}
</style>
