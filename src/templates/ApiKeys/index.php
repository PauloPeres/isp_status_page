<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $apiKeys
 */
$this->assign('title', __('API Keys'));
?>

<style>
    .apikeys-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-create {
        padding: 10px 20px;
        background: #1E88E5;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }

    .btn-create:hover {
        background: #1565C0;
        color: white;
    }

    .apikeys-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .apikeys-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .apikeys-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .apikeys-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        vertical-align: middle;
    }

    .apikeys-table tr:last-child td {
        border-bottom: none;
    }

    .apikeys-table tbody tr:hover {
        background: #f8f9fa;
    }

    .apikeys-table tbody tr.inactive {
        opacity: 0.55;
    }

    .key-prefix {
        font-family: 'Courier New', monospace;
        background: #f3f4f6;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 13px;
        color: #333;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .badge-success {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-info {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .perm-badges {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .action-buttons {
        display: flex;
        gap: 4px;
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        display: inline-block;
    }

    .btn-action-danger {
        background: #ef4444;
        color: white;
    }

    .btn-action-danger:hover {
        background: #dc2626;
    }

    .no-keys {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .pagination {
        margin-top: 24px;
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        color: #666;
        text-decoration: none;
        font-size: 14px;
    }

    .pagination a:hover {
        background: #f8f9fa;
        border-color: #1E88E5;
        color: #1E88E5;
    }

    .pagination .active {
        background: #1E88E5;
        color: white;
        border-color: #1E88E5;
    }

    @media (max-width: 768px) {
        .apikeys-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .apikeys-table {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .apikeys-table table {
            min-width: 600px;
        }

        .action-buttons {
            flex-direction: column;
            gap: 6px;
        }

        .btn-action {
            min-height: 36px;
            text-align: center;
        }
    }
</style>

<div class="apikeys-header">
    <h2><?= __('API Keys') ?></h2>
    <?= $this->Html->link(
        __('+ Create API Key'),
        ['action' => 'add'],
        ['class' => 'btn-create']
    ) ?>
</div>

<div class="apikeys-table">
    <?php if ($apiKeys->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Key Prefix') ?></th>
                    <th><?= __('Permissions') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Last Used') ?></th>
                    <th><?= __('Created') ?></th>
                    <th style="text-align: right;"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apiKeys as $apiKey): ?>
                    <tr class="<?= !$apiKey->active ? 'inactive' : '' ?>">
                        <td>
                            <strong><?= h($apiKey->name) ?></strong>
                            <?php if ($apiKey->user): ?>
                                <br><span style="color: #999; font-size: 12px;"><?= __('by') ?> <?= h($apiKey->user->username) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="key-prefix"><?= h($apiKey->key_prefix) ?>...</span>
                        </td>
                        <td>
                            <div class="perm-badges">
                                <?php foreach ($apiKey->getPermissions() as $perm): ?>
                                    <span class="badge <?= $perm === 'admin' ? 'badge-danger' : ($perm === 'write' ? 'badge-info' : 'badge-secondary') ?>">
                                        <?= h($perm) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!$apiKey->active): ?>
                                <span class="badge badge-danger"><?= __('Revoked') ?></span>
                            <?php elseif ($apiKey->isExpired()): ?>
                                <span class="badge badge-danger"><?= __('Expired') ?></span>
                            <?php else: ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($apiKey->last_used_at): ?>
                                <span class="local-datetime" data-utc="<?= $apiKey->last_used_at->format('c') ?>"></span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;"><?= __('Never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="local-datetime" data-utc="<?= $apiKey->created->format('c') ?>"></span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?php if ($apiKey->active): ?>
                                    <?= $this->Form->postLink(
                                        __('Revoke'),
                                        ['action' => 'delete', $apiKey->id],
                                        [
                                            'class' => 'btn-action btn-action-danger',
                                            'confirm' => __('Are you sure you want to revoke this API key? This action cannot be undone.')
                                        ]
                                    ) ?>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;"><?= __('Revoked') ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-keys">
            <p style="font-size: 18px; margin-bottom: 8px;"><?= __('No API keys found') ?></p>
            <p><?= __('Create an API key to access the REST API programmatically.') ?></p>
        </div>
    <?php endif; ?>
</div>

<?php if ($apiKeys->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__('First')) ?>
        <?= $this->Paginator->prev(__('Previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Next')) ?>
        <?= $this->Paginator->last(__('Last')) ?>
    </div>
<?php endif; ?>
