<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $integrations
 * @var array $stats
 */
$this->assign('title', __('Integracoes'));
?>

<style>
    .integrations-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .btn-add {
        padding: 10px 20px;
        background: #22c55e;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }

    .btn-add:hover {
        background: #16a34a;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card-mini {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .stat-value.success { color: #22c55e; }
    .stat-value.error { color: #ef4444; }
    .stat-value.info { color: #3b82f6; }

    .filters-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .filter-group input,
    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-filter {
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-filter:hover {
        background: #2563eb;
    }

    .btn-clear {
        padding: 8px 16px;
        background: white;
        color: #666;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-clear:hover {
        background: #f8f9fa;
    }

    .table-container {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
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

    .action-buttons {
        display: flex;
        gap: 4px;
        justify-content: flex-end;
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

    .btn-action-view {
        background: #3b82f6;
        color: white;
    }

    .btn-action-view:hover {
        background: #2563eb;
    }

    .btn-action-edit {
        background: #f59e0b;
        color: white;
    }

    .btn-action-edit:hover {
        background: #d97706;
    }

    .btn-action-test {
        background: #22c55e;
        color: white;
    }

    .btn-action-test:hover {
        background: #16a34a;
    }

    .btn-action-danger {
        background: #ef4444;
        color: white;
    }

    .btn-action-danger:hover {
        background: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px !important;
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
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }

    .pagination-info {
        text-align: center;
        margin-top: 12px;
        font-size: 13px;
        color: #666;
    }

    .sync-status {
        font-size: 13px;
        color: #666;
    }

    .test-result {
        display: none;
        margin-top: 8px;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
    }

    .test-result.success {
        background: #dcfce7;
        color: #16a34a;
    }

    .test-result.error {
        background: #fee2e2;
        color: #dc2626;
    }

    @media (max-width: 768px) {
        .integrations-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filters-row {
            grid-template-columns: 1fr;
        }

        .table-container {
            overflow-x: auto;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="integrations-header">
    <h2><?= __('Integracoes') ?></h2>
    <?= $this->Html->link(
        '+ ' . __('Nova Integracao'),
        ['action' => 'add'],
        ['class' => 'btn-add']
    ) ?>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Total') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Ativas') ?></div>
        <div class="stat-value success"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">IXC</div>
        <div class="stat-value"><?= number_format($stats['ixc']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Zabbix</div>
        <div class="stat-value"><?= number_format($stats['zabbix']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">REST API</div>
        <div class="stat-value"><?= number_format($stats['rest_api']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Buscar') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __('Nome da integracao...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Tipo') ?></label>
            <?= $this->Form->control('type', [
                'label' => false,
                'options' => [
                    '' => __('Todos'),
                    'ixc' => 'IXC Soft',
                    'zabbix' => 'Zabbix',
                    'rest_api' => 'REST API',
                ],
                'value' => $this->request->getQuery('type'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Status') ?></label>
            <?= $this->Form->control('active', [
                'label' => false,
                'options' => [
                    '' => __('Todos'),
                    '1' => __('Ativa'),
                    '0' => __('Inativa'),
                ],
                'value' => $this->request->getQuery('active'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>
    </div>

    <div class="filter-buttons">
        <?= $this->Form->button(__('Filtrar'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
        <?= $this->Html->link(__('Limpar'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Integrations Table -->
<div class="table-container">
    <?php if ($integrations->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name', __('Nome')) ?></th>
                    <th><?= $this->Paginator->sort('type', __('Tipo')) ?></th>
                    <th><?= $this->Paginator->sort('active', __('Status')) ?></th>
                    <th><?= $this->Paginator->sort('last_sync_at', __('Ultima Sincronizacao')) ?></th>
                    <th><?= __('Resultado') ?></th>
                    <th style="text-align: right;"><?= __('Acoes') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($integrations as $integration): ?>
                    <tr>
                        <td>
                            <strong><?= h($integration->name) ?></strong>
                        </td>
                        <td>
                            <?php
                            $typeBadge = match ($integration->type) {
                                'ixc' => 'badge-info',
                                'zabbix' => 'badge-warning',
                                'rest_api' => 'badge-secondary',
                                default => 'badge-secondary',
                            };
                            ?>
                            <span class="badge <?= $typeBadge ?>"><?= h($integration->getTypeName()) ?></span>
                        </td>
                        <td>
                            <?php if ($integration->active): ?>
                                <span class="badge badge-success"><?= __('Ativa') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inativa') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($integration->last_sync_at): ?>
                                <span class="sync-status">
                                    <?= $integration->last_sync_at->format('d/m/Y H:i') ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;"><?= __('Nunca') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($integration->last_sync_status): ?>
                                <?php if ($integration->last_sync_status === 'success'): ?>
                                    <span class="badge badge-success"><?= __('OK') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= __('Erro') ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __('Ver'),
                                    ['action' => 'view', $integration->id],
                                    ['class' => 'btn-action btn-action-view']
                                ) ?>
                                <button type="button"
                                        class="btn-action btn-action-test"
                                        onclick="testConnection(<?= $integration->id ?>)"
                                        id="test-btn-<?= $integration->id ?>">
                                    <?= __('Testar') ?>
                                </button>
                                <?= $this->Html->link(
                                    __('Editar'),
                                    ['action' => 'edit', $integration->id],
                                    ['class' => 'btn-action btn-action-edit']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Excluir'),
                                    ['action' => 'delete', $integration->id],
                                    [
                                        'class' => 'btn-action btn-action-danger',
                                        'confirm' => __('Tem certeza que deseja excluir esta integracao? Esta acao nao pode ser desfeita.')
                                    ]
                                ) ?>
                            </div>
                            <div class="test-result" id="test-result-<?= $integration->id ?>"></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <p style="font-size: 18px; margin-bottom: 8px;"><?= __('Nenhuma integracao encontrada') ?></p>
            <p style="margin-bottom: 16px;"><?= __('Configure sua primeira integracao com um sistema externo.') ?></p>
            <?= $this->Html->link(
                __('Nova Integracao'),
                ['action' => 'add'],
                ['class' => 'btn-add']
            ) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($integrations->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first('« ' . __('Primeira')) ?>
        <?= $this->Paginator->prev('< ' . __('Anterior')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Proxima') . ' >') ?>
        <?= $this->Paginator->last(__('Ultima') . ' »') ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__('Pagina {{page}} de {{pages}}, mostrando {{current}} de {{count}} integracoes')) ?>
    </div>
<?php endif; ?>

<script>
function testConnection(integrationId) {
    var btn = document.getElementById('test-btn-' + integrationId);
    var resultDiv = document.getElementById('test-result-' + integrationId);

    btn.disabled = true;
    btn.textContent = 'Testando...';
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
            resultDiv.className = 'test-result success';
            resultDiv.textContent = result.message || 'Conexao bem-sucedida';
            if (result.response_time) {
                resultDiv.textContent += ' (' + Math.round(result.response_time) + 'ms)';
            }
        } else {
            resultDiv.className = 'test-result error';
            resultDiv.textContent = result.error || result.message || 'Falha na conexao';
        }
    })
    .catch(function(err) {
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result error';
        resultDiv.textContent = 'Erro: ' + err.message;
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = '<?= __('Testar') ?>';
    });
}
</script>
