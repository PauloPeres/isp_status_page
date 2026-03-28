<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Integration $integration
 */
$this->assign('title', __('Integration Details'));

$config = $integration->getConfiguration();
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .header-actions {
        display: flex;
        gap: 8px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        display: inline-block;
    }

    .btn-primary {
        background: #f59e0b;
        color: white;
    }

    .btn-primary:hover {
        background: #d97706;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-success {
        background: #22c55e;
        color: white;
    }

    .btn-success:hover {
        background: #16a34a;
    }

    .card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 24px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .detail-item {
        margin-bottom: 12px;
    }

    .detail-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .detail-value {
        font-size: 14px;
        color: #333;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
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

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .config-display {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 16px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        white-space: pre-wrap;
        word-break: break-all;
    }

    .table-container {
        width: 100%;
        overflow: hidden;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-container th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .table-container td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        vertical-align: middle;
    }

    .table-container tr:last-child td {
        border-bottom: none;
    }

    .table-container tbody tr:hover {
        background: #f8f9fa;
    }

    .test-result-card {
        display: none;
        margin-top: 16px;
        padding: 16px;
        border-radius: 6px;
        font-size: 14px;
    }

    .test-result-card.success {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #16a34a;
    }

    .test-result-card.error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .header-actions {
            flex-direction: column;
        }

        .table-container {
            overflow-x: auto;
        }
    }
</style>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Integrations'), 'url' => $this->Url->build(['controller' => 'Integrations', 'action' => 'index'])],
    ['title' => h($integration->name), 'url' => null],
]]) ?>

<div class="page-header">
    <div>
        <h2><?= h($integration->name) ?></h2>
        <p style="color: #666;">
            <?php
            $typeBadge = match ($integration->type) {
                'ixc' => 'badge-info',
                'zabbix' => 'badge-warning',
                'rest_api' => 'badge-secondary',
                default => 'badge-secondary',
            };
            ?>
            <span class="badge <?= $typeBadge ?>"><?= h($integration->getTypeName()) ?></span>
            <?php if ($integration->active): ?>
                <span class="badge badge-success"><?= __('Active') ?></span>
            <?php else: ?>
                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="header-actions">
        <button type="button" class="btn btn-success" onclick="testConnection(<?= $integration->id ?>)" id="test-btn">
            <?= __('Test Connection') ?>
        </button>
        <?= $this->Html->link(
            __('Edit'),
            ['action' => 'edit', $integration->id],
            ['class' => 'btn btn-primary']
        ) ?>
        <?= $this->Html->link(
            __('Back'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>
</div>

<!-- Test Result -->
<div class="test-result-card" id="test-result"></div>

<!-- Integration Details -->
<div class="card">
    <h3 class="card-title"><?= __('General Information') ?></h3>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label"><?= __('Name') ?></div>
            <div class="detail-value"><?= h($integration->name) ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label"><?= __('Type') ?></div>
            <div class="detail-value"><?= h($integration->getTypeName()) ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label"><?= __('Status') ?></div>
            <div class="detail-value">
                <?php if ($integration->active): ?>
                    <span class="badge badge-success"><?= __('Active') ?></span>
                <?php else: ?>
                    <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label"><?= __('Last Sync') ?></div>
            <div class="detail-value">
                <?php if ($integration->last_sync_at): ?>
                    <?= $integration->last_sync_at->nice() ?>
                    <?php if ($integration->last_sync_status === 'success'): ?>
                        <span class="badge badge-success"><?= __('OK') ?></span>
                    <?php elseif ($integration->last_sync_status === 'error'): ?>
                        <span class="badge badge-danger"><?= __('Error') ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #999;"><?= __('Never synced') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label"><?= __('Created') ?></div>
            <div class="detail-value"><?= $integration->created->nice() ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label"><?= __('Modified') ?></div>
            <div class="detail-value"><?= $integration->modified->nice() ?></div>
        </div>
    </div>
</div>

<!-- Configuration -->
<div class="card">
    <h3 class="card-title"><?= __('Configuration') ?></h3>

    <?php if (!empty($config)): ?>
        <div class="detail-grid">
            <?php if (!empty($config['base_url'])): ?>
                <div class="detail-item">
                    <div class="detail-label"><?= __('Base URL') ?></div>
                    <div class="detail-value" style="font-family: 'Courier New', monospace;"><?= h($config['base_url']) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($config['method'])): ?>
                <div class="detail-item">
                    <div class="detail-label"><?= __('Method') ?></div>
                    <div class="detail-value"><?= h($config['method']) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($config['auth_type']) && $config['auth_type'] !== 'none'): ?>
                <div class="detail-item">
                    <div class="detail-label"><?= __('Authentication') ?></div>
                    <div class="detail-value"><?= h(ucfirst($config['auth_type'])) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($config['timeout'])): ?>
                <div class="detail-item">
                    <div class="detail-label"><?= __('Timeout') ?></div>
                    <div class="detail-value"><?= h($config['timeout']) ?>s</div>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Show sanitized config (hide sensitive values)
        $safeConfig = $config;
        $sensitiveKeys = ['api_key', 'password', 'token'];
        foreach ($sensitiveKeys as $key) {
            if (isset($safeConfig[$key]) && !empty($safeConfig[$key])) {
                $safeConfig[$key] = str_repeat('*', 8);
            }
        }
        ?>
        <div style="margin-top: 16px;">
            <div class="detail-label"><?= __('Full Configuration') ?></div>
            <div class="config-display"><?= h(json_encode($safeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
        </div>
    <?php else: ?>
        <p style="color: #999;"><?= __('No configuration defined') ?></p>
    <?php endif; ?>
</div>

<!-- Integration Logs -->
<div class="card">
    <h3 class="card-title"><?= __('Recent Logs') ?></h3>

    <?php if (!empty($integration->integration_logs)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Date') ?></th>
                        <th><?= __('Action') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Message') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($integration->integration_logs as $log): ?>
                        <tr>
                            <td><?= $log->created->nice() ?></td>
                            <td><?= h($log->action) ?></td>
                            <td>
                                <?php if ($log->status === 'success'): ?>
                                    <span class="badge badge-success"><?= __('OK') ?></span>
                                <?php elseif ($log->status === 'error'): ?>
                                    <span class="badge badge-danger"><?= __('Error') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= h($log->status) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($log->message) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p><?= __('No logs recorded') ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
function testConnection(integrationId) {
    var btn = document.getElementById('test-btn');
    var resultDiv = document.getElementById('test-result');

    btn.disabled = true;
    btn.textContent = '<?= __('Testing...') ?>';
    resultDiv.style.display = 'none';

    fetch('<?= $this->Url->build(['action' => 'test']) ?>/' + integrationId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]')?.getAttribute('content') || ''
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        var result = data.result || data;
        resultDiv.style.display = 'block';

        if (result.success) {
            resultDiv.className = 'test-result-card success';
            var msg = result.message || '<?= __('Connection successful') ?>';
            if (result.response_time) {
                msg += ' (<?= __('Response time') ?>: ' + Math.round(result.response_time) + 'ms)';
            }
            if (result.status_code) {
                msg += ' - HTTP ' + result.status_code;
            }
            resultDiv.textContent = msg;
        } else {
            resultDiv.className = 'test-result-card error';
            resultDiv.textContent = result.error || result.message || '<?= __('Connection failed') ?>';
        }
    })
    .catch(function(err) {
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result-card error';
        resultDiv.textContent = '<?= __('Error:') ?> ' + err.message;
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = '<?= __('Test Connection') ?>';
    });
}
</script>
