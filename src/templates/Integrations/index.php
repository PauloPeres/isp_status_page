<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $integrations
 * @var array $stats
 */
$this->assign('title', __('Integracoes'));
?>

<!-- Styles provided by admin.css -->

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
        <?= $this->element('empty_state', [
            'icon' => '🔌',
            'title' => __('No integrations configured'),
            'description' => __('Connect to external systems like IXC, Zabbix, or REST APIs.'),
            'actionUrl' => $this->Url->build(['action' => 'add']),
            'actionLabel' => __('Add Integration'),
        ]) ?>
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
