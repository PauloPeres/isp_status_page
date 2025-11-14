<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Subscriber> $subscribers
 * @var array $stats
 * @var string $period
 */
$this->assign('title', __d('subscribers', 'Inscritos de Notificações'));
?>

<style>
    .subscribers-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    .stat-value.warning { color: #f59e0b; }

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
        align-items: end;
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

    .filter-group select,
    .filter-group input {
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

    .subscribers-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .subscribers-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .subscribers-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .subscribers-table th a {
        color: #666;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .subscribers-table th a:hover {
        color: #3b82f6;
    }

    .subscribers-table th a::after {
        content: '⇅';
        opacity: 0.3;
        font-size: 12px;
    }

    .subscribers-table th a.asc::after {
        content: '↑';
        opacity: 1;
        color: #3b82f6;
    }

    .subscribers-table th a.desc::after {
        content: '↓';
        opacity: 1;
        color: #3b82f6;
    }

    .subscribers-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .subscribers-table tr:last-child td {
        border-bottom: none;
    }

    .subscribers-table tr:hover {
        background: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-success {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-info {
        background: #dbeafe;
        color: #2563eb;
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

    .btn-action-toggle {
        background: #8b5cf6;
        color: white;
    }

    .btn-action-toggle:hover {
        background: #7c3aed;
    }

    .btn-action-danger {
        background: #ef4444;
        color: white;
    }

    .btn-action-danger:hover {
        background: #dc2626;
    }

    .no-subscribers {
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

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filters-row {
            grid-template-columns: 1fr;
        }

        .subscribers-table {
            overflow-x: auto;
        }
    }
</style>

<div class="subscribers-header">
    <h2><?= __d('subscribers', 'Inscritos de Notificações') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Total de Inscritos') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Verificados') ?></div>
        <div class="stat-value success"><?= number_format($stats['verified']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Não Verificados') ?></div>
        <div class="stat-value warning"><?= number_format($stats['unverified']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Ativos') ?></div>
        <div class="stat-value success"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Adicionados (7 dias)') ?></div>
        <div class="stat-value info"><?= number_format($stats['recentlyAdded']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __d('subscribers', 'Buscar') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __d('subscribers', 'Email ou nome...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Status de Verificação') ?></label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => __d('subscribers', 'Todos'),
                    'verified' => __d('subscribers', 'Verificados'),
                    'unverified' => __d('subscribers', 'Não Verificados'),
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Status Ativo') ?></label>
            <?= $this->Form->control('active', [
                'options' => [
                    '' => __d('subscribers', 'Todos'),
                    'active' => __d('subscribers', 'Ativos'),
                    'inactive' => __d('subscribers', 'Inativos'),
                ],
                'default' => $this->request->getQuery('active'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Período') ?></label>
            <?= $this->Form->control('period', [
                'options' => [
                    '7d' => __d('subscribers', 'Últimos 7 dias'),
                    '30d' => __d('subscribers', 'Últimos 30 dias'),
                    '90d' => __d('subscribers', 'Últimos 90 dias'),
                    'all' => __d('subscribers', 'Todos'),
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__d('subscribers', 'Filtrar'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__d('subscribers', 'Limpar'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Subscribers Table -->
<div class="subscribers-table">
    <?php if ($subscribers->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('email', __d('subscribers', 'Email / Nome')) ?></th>
                    <th><?= $this->Paginator->sort('verified', __d('subscribers', 'Verificação')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('subscribers', 'Status')) ?></th>
                    <th><?= $this->Paginator->sort('created', __d('subscribers', 'Data de Inscrição')) ?></th>
                    <th><?= __d('subscribers', 'Assinaturas') ?></th>
                    <th style="text-align: right;"><?= __d('subscribers', 'Ações') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td>
                            <strong><?= h($subscriber->email) ?></strong>
                            <?php if ($subscriber->name): ?>
                                <br>
                                <span style="color: #999; font-size: 13px;">
                                    <?= h($subscriber->name) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($subscriber->verified): ?>
                                <span class="badge badge-success"><?= __d('subscribers', 'Verificado') ?></span>
                                <?php if ($subscriber->verified_at): ?>
                                    <br>
                                    <span style="color: #999; font-size: 12px;">
                                        <?= $subscriber->verified_at->i18nFormat('dd/MM/yyyy HH:mm') ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning"><?= __d('subscribers', 'Pendente') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($subscriber->active): ?>
                                <span class="badge badge-success"><?= __d('subscribers', 'Ativo') ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?= __d('subscribers', 'Inativo') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= h($subscriber->created->i18nFormat('dd/MM/yyyy')) ?></strong>
                            <br>
                            <span style="color: #666; font-size: 13px;">
                                <?= h($subscriber->created->i18nFormat('HH:mm:ss')) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (isset($subscriber->subscriptions) && count($subscriber->subscriptions) > 0): ?>
                                <span class="badge badge-info">
                                    <?= count($subscriber->subscriptions) ?> <?= count($subscriber->subscriptions) > 1 ? __d('subscribers', 'monitores') : __d('subscribers', 'monitor') ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;"><?= __d('subscribers', 'Nenhuma') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __d('subscribers', 'Ver'),
                                    ['action' => 'view', $subscriber->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __d('subscribers', 'Ver detalhes')]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $subscriber->active ? __d('subscribers', 'Desativar') : __d('subscribers', 'Ativar'),
                                    ['action' => 'toggle', $subscriber->id],
                                    [
                                        'class' => 'btn-action btn-action-toggle',
                                        'title' => $subscriber->active ? __d('subscribers', 'Desativar inscrito') : __d('subscribers', 'Ativar inscrito'),
                                        'confirm' => __d('subscribers', 'Tem certeza que deseja {0} este inscrito?', $subscriber->active ? __d('subscribers', 'desativar') : __d('subscribers', 'ativar'))
                                    ]
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('subscribers', 'Excluir'),
                                    ['action' => 'delete', $subscriber->id],
                                    [
                                        'class' => 'btn-action btn-action-danger',
                                        'title' => __d('subscribers', 'Excluir inscrito'),
                                        'confirm' => __d('subscribers', 'Tem certeza que deseja excluir este inscrito? Esta ação não pode ser desfeita.')
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-subscribers">
            <p style="font-size: 18px; margin-bottom: 8px;"><?= __d('subscribers', 'Nenhum inscrito encontrado') ?></p>
            <p><?= __d('subscribers', 'Tente ajustar os filtros ou aguarde novas inscrições.') ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($subscribers->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__d('subscribers', '« Primeira')) ?>
        <?= $this->Paginator->prev(__d('subscribers', '‹ Anterior')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('subscribers', 'Próxima ›')) ?>
        <?= $this->Paginator->last(__d('subscribers', 'Última »')) ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__d('subscribers', 'Página {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} no total')) ?>
    </div>
<?php endif; ?>
